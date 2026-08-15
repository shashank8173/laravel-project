{{-- Modern role-based sidebar --}}
@php
    $user = auth()->user();
    $badges = $layoutBadges ?? [];
    $isAdmin = (bool) $user?->isAdmin();
    $canAdminDashboard = (bool) $user?->canAccessAdminDashboard();
    $isSuperAdmin = (bool) $user?->isSuperAdmin();
    $isManager = (bool) $user?->isReportingManager();
    $initials = 'U';
    if ($user) {
        $parts = preg_split('/\s+/', trim($user->full_name)) ?: [];
        $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1).substr($parts[1] ?? '', 0, 1)) ?: 'U';
    }
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
        <div class="brand">
            <span class="brand-mark">
                <img src="{{ $appLogoUrl ?? asset('assets/img/logo2.png') }}" alt="" onerror="this.remove()">
            </span>
            <span class="brand-text">
                <span class="brand-name">Leadforgrow</span>
                <span class="brand-sub">HRM MANAGEMENT</span>
            </span>
        </div>

        <div id="sidebar-menu" class="sidebar-menu">
            <ul class="sidebar-vertical list-unstyled mb-0">

                <li class="nav-section">Overview</li>

                {{-- Dashboard --}}
                @if($canAdminDashboard)
                    <li>
                        <a href="{{ route('dashboard.admin') }}" class="{{ request()->routeIs('dashboard.admin') ? 'active' : '' }}">
                            <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
                        </a>
                    </li>
                @else
                    <li>
                        <a href="{{ route('dashboard.employee') }}" class="{{ request()->routeIs('dashboard.employee') ? 'active' : '' }}">
                            <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
                        </a>
                    </li>
                @endif

                @if($isAdmin)
                    <li class="nav-section">People</li>
                    <li>
                        <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.index') || request()->routeIs('employees.create') || request()->routeIs('employees.show') || request()->routeIs('employees.edit') ? 'active' : '' }}">
                            <i class="fas fa-user"></i><span>All Employees</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('holidays.index') }}" class="{{ request()->routeIs('holidays.*') ? 'active' : '' }}">
                            <i class="fas fa-snowflake"></i><span>Holidays</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('leaves.admin') }}" class="{{ request()->routeIs('leaves.admin') ? 'active' : '' }}">
                            <i class="fas fa-clock"></i><span>Leaves (Admin)</span>
                            @if(($badges['pending_leaves'] ?? 0) > 0)
                                <span class="sidebar-badge">{{ $badges['pending_leaves'] }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-section">Operations</li>
                    <li class="submenu {{ request()->routeIs('attendance.*') ? 'open' : '' }}">
                        <a href="#"><i class="fas fa-calendar-check"></i><span>Attendance</span><span class="menu-arrow"><i class="fa-solid fa-chevron-right"></i></span></a>
                        <ul>
                            <li><a href="{{ route('attendance.admin') }}" class="{{ request()->routeIs('attendance.admin') ? 'active' : '' }}"><span>Attendance (MN)</span></a></li>
                            <li><a href="{{ route('attendance.upload') }}" class="{{ request()->routeIs('attendance.upload*') ? 'active' : '' }}"><span>Upload Attendance</span></a></li>
                            <li><a href="{{ route('attendance.all') }}" class="{{ request()->routeIs('attendance.all') ? 'active' : '' }}"><span>Attendance All (HRM)</span></a></li>
                        </ul>
                    </li>

                    <li class="submenu {{ request()->routeIs('departments.*') || request()->routeIs('designations.*') || request()->routeIs('companies.*') || request()->routeIs('policies.*') || request()->routeIs('company-data.*') ? 'open' : '' }}">
                        <a href="#"><i class="fas fa-building"></i><span>Company</span><span class="menu-arrow"><i class="fa-solid fa-chevron-right"></i></span></a>
                        <ul>
                            <li><a href="{{ route('departments.index') }}" class="{{ request()->routeIs('departments.*') ? 'active' : '' }}"><span>Departments</span></a></li>
                            <li><a href="{{ route('designations.index') }}" class="{{ request()->routeIs('designations.*') ? 'active' : '' }}"><span>Designations</span></a></li>
                            <li><a href="{{ route('policies.index') }}" class="{{ request()->routeIs('policies.*') ? 'active' : '' }}"><span>Company Policies</span></a></li>
                            <li><a href="{{ route('company-data.index') }}" class="{{ request()->routeIs('company-data.*') ? 'active' : '' }}"><span>Company Data</span></a></li>
                            <li><a href="{{ route('companies.index') }}" class="{{ request()->routeIs('companies.*') ? 'active' : '' }}"><span>Company Details</span></a></li>
                        </ul>
                    </li>

                    <li class="submenu {{ request()->routeIs('eom.*') || request()->routeIs('activities.*') || request()->routeIs('settings.notifications*') ? 'open' : '' }}">
                        <a href="#"><i class="fas fa-bullhorn"></i><span>Announcement</span><span class="menu-arrow"><i class="fa-solid fa-chevron-right"></i></span></a>
                        <ul>
                            <li><a href="{{ route('eom.index') }}" class="{{ request()->routeIs('eom.*') ? 'active' : '' }}"><span>Employee of the Month</span></a></li>
                            <li><a href="{{ route('activities.index') }}" class="{{ request()->routeIs('activities.*') ? 'active' : '' }}"><span>Add Announcement</span></a></li>
                        </ul>
                    </li>

                    <li class="nav-section">Workflow</li>
                    <li>
                        <a href="{{ route('tickets.manage') }}" class="{{ request()->routeIs('tickets.manage') || request()->routeIs('tickets.show') ? 'active' : '' }}">
                            <i class="fas fa-ticket-alt"></i><span>Ticket</span>
                            @if(($badges['open_tickets'] ?? 0) > 0)
                                <span class="sidebar-badge">{{ $badges['open_tickets'] }}</span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('resignation.admin') }}" class="{{ request()->routeIs('resignation.admin') || request()->routeIs('resignation.status') ? 'active' : '' }}">
                            <i class="fas fa-file-signature"></i><span>Resignations</span>
                            @if(($badges['pending_resignations'] ?? 0) > 0)
                                <span class="sidebar-badge">{{ $badges['pending_resignations'] }}</span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('notice-period.index') }}" class="{{ request()->routeIs('notice-period.*') || request()->routeIs('notice-period-steps.*') ? 'active' : '' }}">
                            <i class="fas fa-sign-out-alt"></i><span>Notice Period</span>
                        </a>
                    </li>

                    <li class="submenu {{ request()->routeIs('posh.*') || request()->routeIs('harassment.*') ? 'open' : '' }}">
                        <a href="#"><i class="fa-solid fa-shield-halved"></i><span>POSH</span><span class="menu-arrow"><i class="fa-solid fa-chevron-right"></i></span></a>
                        <ul>
                            <li><a href="{{ route('posh.guidelines') }}" class="{{ request()->routeIs('posh.guidelines') ? 'active' : '' }}"><span>Guidelines</span></a></li>
                            <li><a href="{{ route('posh.committee') }}" class="{{ request()->routeIs('posh.committee') ? 'active' : '' }}"><span>Committee</span></a></li>
                            <li><a href="{{ route('harassment.create') }}" class="{{ request()->routeIs('harassment.create') ? 'active' : '' }}"><span>Complain</span></a></li>
                            <li><a href="{{ route('harassment.admin') }}" class="{{ request()->routeIs('harassment.admin') ? 'active' : '' }}"><span>All Complaints</span></a></li>
                        </ul>
                    </li>

                @else
                    <li class="nav-section">My work</li>
                    <li>
                        <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-diagram-project"></i><span>Projects</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('leaves.employee') }}" class="{{ request()->routeIs('leaves.employee') ? 'active' : '' }}">
                            <i class="fa-solid fa-user-clock"></i><span>My Leaves</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('attendance.mine') }}" class="{{ request()->routeIs('attendance.mine') ? 'active' : '' }}">
                            <i class="fa-solid fa-clipboard-list"></i><span>Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('attendance.all') }}" class="{{ request()->routeIs('attendance.all') ? 'active' : '' }}">
                            <i class="fa-solid fa-calendar-day"></i><span>Daily Attendance</span>
                        </a>
                    </li>

                    @if($isManager)
                        <li class="nav-section">Team</li>
                        <li>
                            <a href="{{ route('attendance.all') }}" class="{{ request()->routeIs('attendance.all') ? 'active' : '' }}">
                                <i class="fas fa-calendar-check"></i><span>Employee Attendance</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('leaves.admin') }}" class="{{ request()->routeIs('leaves.admin') ? 'active' : '' }}">
                                <i class="fas fa-clock"></i><span>Employee Leaves</span>
                            </a>
                        </li>
                    @endif

                    <li class="nav-section">Workplace</li>
                    <li>
                        <a href="{{ route('companies.index') }}" class="{{ request()->routeIs('companies.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-briefcase"></i><span>Company Details</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('expenses.mine') }}" class="{{ request()->routeIs('expenses.mine') ? 'active' : '' }}">
                            <i class="fa-solid fa-receipt"></i><span>Expenses</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('tickets.index') }}" class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-ticket"></i><span>Ticket</span>
                        </a>
                    </li>

                    <li class="submenu {{ request()->routeIs('posh.*') || request()->routeIs('harassment.*') ? 'open' : '' }}">
                        <a href="#"><i class="fa-solid fa-shield-halved"></i><span>POSH</span><span class="menu-arrow"><i class="fa-solid fa-chevron-right"></i></span></a>
                        <ul>
                            <li><a href="{{ route('posh.guidelines') }}" class="{{ request()->routeIs('posh.guidelines') ? 'active' : '' }}"><span>Guidelines</span></a></li>
                            <li><a href="{{ route('posh.committee') }}" class="{{ request()->routeIs('posh.committee') ? 'active' : '' }}"><span>Committee</span></a></li>
                            <li><a href="{{ route('harassment.create') }}" class="{{ request()->routeIs('harassment.create') ? 'active' : '' }}"><span>Complain</span></a></li>
                        </ul>
                    </li>
                @endif

                @if($isAdmin)
                    <li class="nav-section">Management</li>
                    <li class="submenu {{ request()->routeIs('assets.*') || request()->routeIs('settings.office-timing*') || request()->routeIs('settings.greetings*') || request()->routeIs('salary.*') || request()->routeIs('onboarding.*') || request()->routeIs('employees.archived*') || request()->routeIs('expenses.admin') || request()->routeIs('expenses.status') || request()->routeIs('expenses.categories*') || request()->routeIs('expenses.companies*') || request()->routeIs('analytics.*') || request()->routeIs('overtime.*') || request()->routeIs('candidates.*') || request()->routeIs('reporting.*') || request()->routeIs('projects.*') ? 'open' : '' }}">
                        <a href="#"><i class="fa-solid fa-briefcase"></i><span>Management</span><span class="menu-arrow"><i class="fa-solid fa-chevron-right"></i></span></a>
                        <ul>
                            <li><a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}"><span>Project Management</span></a></li>
                            <li><a href="{{ route('assets.assignments') }}" class="{{ request()->routeIs('assets.*') ? 'active' : '' }}"><span>Asset Management</span></a></li>
                            <li><a href="{{ route('settings.office-timing') }}" class="{{ request()->routeIs('settings.office-timing*') ? 'active' : '' }}"><span>Office Timing</span></a></li>
                            <li><a href="{{ route('settings.greetings') }}" class="{{ request()->routeIs('settings.greetings*') ? 'active' : '' }}"><span>Greeting Cards</span></a></li>
                            <li><a href="{{ route('employees.archived') }}" class="{{ request()->routeIs('employees.archived*') ? 'active' : '' }}"><span>Former Employee</span></a></li>
                            <li><a href="{{ route('salary.index') }}" class="{{ request()->routeIs('salary.index') || request()->routeIs('salary.update') ? 'active' : '' }}"><span>Salary Management</span></a></li>
                            <li><a href="{{ route('salary.calculate') }}" class="{{ request()->routeIs('salary.calculate') ? 'active' : '' }}"><span>Salary Admin</span></a></li>
                            <li><a href="{{ route('onboarding.index') }}" class="{{ request()->routeIs('onboarding.*') ? 'active' : '' }}"><span>Onboarding Management</span></a></li>
                            <li><a href="{{ route('expenses.admin') }}" class="{{ request()->routeIs('expenses.admin*') || request()->routeIs('expenses.status') || request()->routeIs('expenses.categories*') || request()->routeIs('expenses.companies*') ? 'active' : '' }}"><span>Expenses Management</span></a></li>
                            <li><a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active' : '' }}"><span>Analytics</span></a></li>
                            <li><a href="{{ route('overtime.index') }}" class="{{ request()->routeIs('overtime.*') ? 'active' : '' }}"><span>Overtime</span></a></li>
                            <li><a href="{{ route('candidates.index') }}" class="{{ request()->routeIs('candidates.*') ? 'active' : '' }}"><span>Candidates</span></a></li>
                            <li><a href="{{ route('reporting.index') }}" class="{{ request()->routeIs('reporting.*') ? 'active' : '' }}"><span>Reporting Managers</span></a></li>
                        </ul>
                    </li>
                @endif

                <li class="nav-section">Account</li>
                <li class="submenu {{ request()->routeIs('settings.notifications*') || request()->routeIs('resignation.mine') || request()->routeIs('password.*') || request()->routeIs('contacts.*') ? 'open' : '' }}">
                    <a href="#"><i class="fa-solid fa-gear"></i><span>Settings</span><span class="menu-arrow"><i class="fa-solid fa-chevron-right"></i></span></a>
                    <ul>
                        <li><a href="{{ route('settings.notifications') }}" class="{{ request()->routeIs('settings.notifications*') ? 'active' : '' }}"><span>Notifications</span></a></li>
                        <li><a href="{{ route('resignation.mine') }}" class="{{ request()->routeIs('resignation.mine') ? 'active' : '' }}"><span>Resignation Form</span></a></li>
                        <li><a href="{{ route('password.edit') }}" class="{{ request()->routeIs('password.*') ? 'active' : '' }}"><span>Change Password</span></a></li>
                        <li><a href="{{ route('contacts.index') }}" class="{{ request()->routeIs('contacts.*') ? 'active' : '' }}"><span>Contacts</span></a></li>
                    </ul>
                </li>

                @if($isSuperAdmin)
                    <li class="nav-section">System</li>
                    <li class="submenu {{ request()->routeIs('roles.*') || request()->routeIs('settings.email*') || request()->routeIs('developer.*') || request()->routeIs('logs.admin') ? 'open' : '' }}">
                        <a href="#"><i class="fa-solid fa-code"></i><span>Developer</span><span class="menu-arrow"><i class="fa-solid fa-chevron-right"></i></span></a>
                        <ul>
                            <li><a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}"><span>Users role</span></a></li>
                            <li><a href="{{ route('developer.optional-setup') }}" class="{{ request()->routeIs('developer.optional-setup*') ? 'active' : '' }}"><span>Optional Setup</span></a></li>
                            <li><a href="{{ route('developer.api-tokens') }}" class="{{ request()->routeIs('developer.api-tokens*') ? 'active' : '' }}"><span>API Tokens</span></a></li>
                            <li><a href="{{ route('developer.connectors') }}" class="{{ request()->routeIs('developer.connectors*') ? 'active' : '' }}"><span>Connectors</span></a></li>
                            <li><a href="{{ route('developer.connector-training') }}" class="{{ request()->routeIs('developer.connector-training') ? 'active' : '' }}"><span>Connector Training</span></a></li>
                            <li><a href="{{ route('developer.integration-guide') }}" class="{{ request()->routeIs('developer.integration-guide') ? 'active' : '' }}"><span>Integration Guide</span></a></li>
                            <li><a href="{{ route('settings.email') }}" class="{{ request()->routeIs('settings.email*') ? 'active' : '' }}"><span>Email Settings</span></a></li>
                            <li><a href="{{ route('developer.passwords') }}" class="{{ request()->routeIs('developer.passwords*') ? 'active' : '' }}"><span>Password Generate</span></a></li>
                            <li><a href="{{ route('developer.branding') }}" class="{{ request()->routeIs('developer.branding*') ? 'active' : '' }}"><span>Logo &amp; Icon</span></a></li>
                            <li><a href="{{ route('developer.cron') }}" class="{{ request()->routeIs('developer.cron*') ? 'active' : '' }}"><span>Cron Jobs</span></a></li>
                            <li><a href="{{ route('logs.admin') }}" class="{{ request()->routeIs('logs.admin') ? 'active' : '' }}"><span>Admin Logs</span></a></li>
                        </ul>
                    </li>
                @endif
            </ul>
        </div>

        @if($user)
            <div class="sidebar-foot">
                <a href="{{ route('profile.show') }}" class="sidebar-user" title="My profile">
                    <img src="{{ $user->profile_image_url }}" alt=""
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                    <span class="sb-avatar" style="display:none;">{{ $initials ?: 'U' }}</span>
                    <span class="sb-meta">
                        <span class="sb-name">{{ $user->full_name }}</span>
                        <span class="sb-role">{{ $user->role ?: 'user' }}</span>
                    </span>
                </a>
            </div>
        @endif
    </div>
</aside>
