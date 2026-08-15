<?php

namespace App\Http\Controllers;

use App\Models\EmailConfiguration;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Services\HrmMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $status = $request->get('status');
        $q = trim((string) $request->get('q', ''));

        $base = Ticket::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('EmployeeID', $user->id));

        $tickets = (clone $base)
            ->with(['category', 'employee'])
            ->when($status, fn ($query) => $query->where('Status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('Title', 'like', "%{$q}%")
                        ->orWhere('TicketID', 'like', "%{$q}%")
                        ->orWhere('Description', 'like', "%{$q}%")
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->latest('CreatedAt')
            ->paginate(25)
            ->withQueryString();

        $categories = TicketCategory::query()->where('status', 1)->orderBy('name')->get();

        $counts = [
            'all' => (clone $base)->count(),
            'open' => (clone $base)->where('Status', 'Open')->count(),
            'in_progress' => (clone $base)->where('Status', 'In Progress')->count(),
            'resolved' => (clone $base)->where('Status', 'Resolved')->count(),
            'closed' => (clone $base)->where('Status', 'Closed')->count(),
        ];

        return view('tickets.index', compact('tickets', 'categories', 'status', 'q', 'counts'));
    }

    /**
     * Legacy view-ticket.php — HR ticket management for all tickets.
     * Also hosts legacy category.php CRUD in the Categories tab.
     */
    public function manage(Request $request): View
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $tab = $request->get('tab', 'tickets') === 'categories' ? 'categories' : 'tickets';

        $tickets = Ticket::query()
            ->with(['category', 'employee', 'comments.author'])
            ->latest('CreatedAt')
            ->get();

        $categories = TicketCategory::query()
            ->orderBy('name')
            ->get();

        $counts = [
            'open' => Ticket::query()->where('Status', 'Open')->count(),
            'in_progress' => Ticket::query()->where('Status', 'In Progress')->count(),
            'resolved' => Ticket::query()
                ->where('Status', 'Resolved')
                ->whereMonth('CreatedAt', now()->month)
                ->whereYear('CreatedAt', now()->year)
                ->count(),
            'closed' => Ticket::query()
                ->where('Status', 'Closed')
                ->whereMonth('CreatedAt', now()->month)
                ->whereYear('CreatedAt', now()->year)
                ->count(),
        ];

        return view('tickets.manage', compact('tickets', 'counts', 'categories', 'tab'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        TicketCategory::create([
            'name' => trim($data['name']),
            'status' => 1,
        ]);

        return redirect()
            ->route('tickets.manage', ['tab' => 'categories'])
            ->with('success', 'Category added successfully.');
    }

    public function updateCategory(Request $request, TicketCategory $category): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->update(['name' => trim($data['name'])]);

        return redirect()
            ->route('tickets.manage', ['tab' => 'categories'])
            ->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(TicketCategory $category): RedirectResponse
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        $inUse = Ticket::query()->where('CategoryID', $category->id)->exists();
        if ($inUse) {
            return redirect()
                ->route('tickets.manage', ['tab' => 'categories'])
                ->withErrors(['category' => 'Cannot delete category that is used by existing tickets.']);
        }

        $category->delete();

        return redirect()
            ->route('tickets.manage', ['tab' => 'categories'])
            ->with('success', 'Category deleted successfully.');
    }

    public function store(Request $request, HrmMailer $mailer): RedirectResponse
    {
        $data = $request->validate([
            'CategoryID' => ['required', 'integer', 'exists:hrm_ticket_categories,id'],
            'Title' => ['required', 'string', 'max:255'],
            'Description' => ['nullable', 'string'],
            'Priority' => ['required', 'in:Low,Medium,High'],
        ]);

        $user = Auth::user();
        $ticket = Ticket::create([
            ...$data,
            'Status' => 'Open',
            'EmployeeID' => $user->id,
        ]);

        $subject = "Ticket Created: Ticket ID {$ticket->TicketID}";
        $message = 'A new ticket has been created by '.e($user->full_name).'.<br><br>'
            .'<strong>Ticket Details:</strong><br>'
            .'Ticket ID: '.$ticket->TicketID.'<br>'
            .'Title: '.e($ticket->Title).'<br>'
            .'Description: '.nl2br(e((string) $ticket->Description)).'<br>'
            .'Priority: '.e($ticket->Priority).'<br>'
            .'Employee: '.e($user->full_name);

        [$hrEmail, $cc] = $this->supportRecipients($user->officialEmail());
        if ($hrEmail) {
            $mailer->send($hrEmail, $subject, $message, $cc);
        }

        return back()->with('success', 'Ticket created successfully!');
    }

    public function show(Ticket $ticket): View
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || (int) $ticket->EmployeeID === (int) $user->id, 403);

        $ticket->load(['category', 'employee', 'comments.author']);
        $allowedStatuses = $this->allowedStatusesFor($user, $ticket);

        return view('tickets.show', compact('ticket', 'allowedStatuses'));
    }

    public function updateStatus(Request $request, Ticket $ticket, HrmMailer $mailer): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || (int) $ticket->EmployeeID === (int) $user->id, 403);

        $allowed = $this->allowedStatusesFor($user, $ticket);
        if ($allowed === []) {
            return back()->withErrors(['Status' => 'You cannot change status on this ticket yet.']);
        }

        $data = $request->validate([
            'Status' => ['required', 'in:'.implode(',', $allowed)],
            'Rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $ticket->load('employee');

        if ($data['Status'] === 'Resolved' && $user->isAdmin()) {
            $ticket->Priority = 'Low';
        }

        if ($data['Status'] === 'Closed' && isset($data['Rating'])) {
            $ticket->Rating = $data['Rating'];
        }

        $ticket->Status = $data['Status'];
        $ticket->save();

        $commentText = trim((string) ($data['comment'] ?? ''));
        if ($commentText !== '') {
            TicketComment::create([
                'ticket_id' => $ticket->TicketID,
                'comment' => $commentText,
                'commented_by' => $user->id,
                'created_at' => now(),
            ]);
        }

        $subject = "Ticket Status Updated: Ticket ID {$ticket->TicketID}";
        $message = "The status of Ticket ID: {$ticket->TicketID} has been updated to <strong>".e($data['Status']).'</strong>.<br><br>'
            .'<strong>Ticket Details:</strong><br>'
            .'Title: '.e($ticket->Title).'<br>'
            .'Description: '.nl2br(e((string) $ticket->Description)).'<br>'
            .'Priority: '.e($ticket->Priority).'<br>'
            .'Employee: '.e($ticket->employee?->full_name ?? '').'<br>';

        if ($commentText !== '') {
            $message .= '<strong>Latest Comment:</strong><br>'.nl2br(e($commentText)).'<br>';
        }

        $employeeEmail = $ticket->employee?->officialEmail();
        [$hrEmail, $cc] = $this->supportRecipients($employeeEmail);

        if ($hrEmail) {
            $mailer->send($hrEmail, $subject, $message, $cc);
        }

        if ($employeeEmail) {
            $mailer->send($employeeEmail, $subject, $message, $this->supportCc());
        }

        return back()->with('success', 'Ticket status updated successfully!');
    }

    public function comment(Request $request, Ticket $ticket, HrmMailer $mailer): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isAdmin() || (int) $ticket->EmployeeID === (int) $user->id, 403);

        if ($ticket->Status === 'Closed') {
            return back()->withErrors(['comment' => 'Cannot add comments to a closed ticket.']);
        }

        $data = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        $ticket->load('employee');

        TicketComment::create([
            'ticket_id' => $ticket->TicketID,
            'comment' => $data['comment'],
            'commented_by' => $user->id,
            'created_at' => now(),
        ]);

        $subject = "New Comment on Ticket ID {$ticket->TicketID} by {$user->full_name}";
        $message = 'A new comment has been added to Ticket ID: '.$ticket->TicketID.' by '.e($user->full_name).'.<br><br>'
            .'<strong>Ticket Details:</strong><br>'
            .'Title: '.e($ticket->Title).'<br>'
            .'Description: '.nl2br(e((string) $ticket->Description)).'<br>'
            .'Priority: '.e($ticket->Priority).'<br>'
            .'Employee: '.e($ticket->employee?->full_name ?? '').'<br><br>'
            .'<strong>Latest Comment:</strong><br>'
            .nl2br(e($data['comment']));

        $creatorEmail = $ticket->employee?->officialEmail();
        [$hrEmail, $cc] = $this->supportRecipients($creatorEmail);
        if ($hrEmail) {
            $mailer->send($hrEmail, $subject, $message, $cc);
        }

        return back()->with('success', 'Comment added successfully!');
    }

    /**
     * Statuses the current user may set on this ticket.
     * Admins: full workflow. Owners: close after resolve / reopen closed tickets.
     *
     * @return list<string>
     */
    private function allowedStatusesFor($user, Ticket $ticket): array
    {
        $all = ['Open', 'In Progress', 'Resolved', 'Closed', 'Reopened'];

        if ($user->isAdmin()) {
            return $all;
        }

        $current = (string) $ticket->Status;

        return match ($current) {
            'Resolved' => ['Resolved', 'Closed', 'Reopened'],
            'Closed' => ['Closed', 'Reopened'],
            'Reopened' => ['Reopened', 'Closed'],
            default => [], // Open / In Progress — HR-managed only
        };
    }

    /**
     * @return array{0:?string,1:list<string>}
     */
    private function supportRecipients(?string $extraCc = null): array
    {
        $to = trim((string) EmailConfiguration::getValue('SUPPORT_TO', ''));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $to = trim((string) (EmailConfiguration::docsFrom()['email']
                ?? EmailConfiguration::getValue('FROM_EMAIL')
                ?? ''));
        }

        $cc = $this->supportCc();
        if ($extraCc && filter_var($extraCc, FILTER_VALIDATE_EMAIL)) {
            $cc[] = $extraCc;
        }

        $cc = array_values(array_unique(array_filter(
            $cc,
            fn ($email) => $email && strcasecmp((string) $email, (string) $to) !== 0
        )));

        return [$to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL) ? $to : null, $cc];
    }

    /**
     * @return list<string>
     */
    private function supportCc(): array
    {
        return array_values(array_filter(
            EmailConfiguration::recipients('SUPPORT_CC'),
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
        ));
    }
}
