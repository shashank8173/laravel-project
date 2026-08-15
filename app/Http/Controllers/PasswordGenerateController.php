<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordGenerateController extends Controller
{
    /**
     * Legacy passwordgenerate.php — random plaintext password reset (matches legacy login).
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $employees = Employee::query()
            ->where('archive_status', 0)
            ->where('id', '!=', 14)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('fname', 'like', "%{$q}%")
                        ->orWhere('lname', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('office_email', 'like', "%{$q}%")
                        ->orWhere('mobile1', 'like', "%{$q}%")
                        ->orWhere('emp_id', 'like', "%{$q}%");
                });
            })
            ->orderBy('fname')
            ->paginate(50)
            ->withQueryString();

        return view('developer.password-generate', compact('employees', 'q'));
    }

    public function resetOne(Request $request, Employee $employee): JsonResponse
    {
        $password = $this->generatePassword();
        $employee->password = $password;
        $employee->save();

        return response()->json([
            'status' => 'success',
            'password' => $password,
            'employee_id' => $employee->id,
            'name' => $employee->full_name,
        ]);
    }

    public function resetBulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer', 'exists:hrm_employee,id'],
        ]);

        $updated = [];
        foreach ($data['selected_ids'] as $id) {
            $employee = Employee::query()->find($id);
            if (! $employee) {
                continue;
            }
            $password = $this->generatePassword();
            $employee->password = $password;
            $employee->save();
            $updated[] = [
                'id' => $employee->id,
                'password' => $password,
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => count($updated).' password(s) updated successfully.',
            'updated' => $updated,
        ]);
    }

    private function generatePassword(int $length = 10): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#&!';
        $max = strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, $max)];
        }

        return $out;
    }
}
