<?php

use App\Models\Process;
use Illuminate\Database\Eloquent\Builder;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view([
            'pageTitle' => $this->pageTitle,
            'statistics' => $this->statistics
        ])->title($this->pageTitle);
    }

    #[On('refresh')]
    public function refresh() {}

    #[Computed]
    public function pageTitle()
    {
        return 'Dashboard';
    }

    #[Computed]
    public function statistics(): array
    {
        return [
            'total' => $this->getTotalProcesses(),
            'pending' => $this->getPendingProcesses(),
            'awaitingApproval' => $this->getAwaitingApprovalProcesses(),
            'approved' => $this->getApprovedProcesses(),
            'failed' => $this->getFailedProcesses(),
            'average' => $this->getAverageApprovalTime(),
        ];
    }

    private function getTotalProcesses(): int
    {
        return $this->processesQuery()->count();
    }

    private function getPendingProcesses(): int
    {
        return $this->processesQuery()->where('status', 'draft')->count();
    }

    private function getAwaitingApprovalProcesses(): int
    {
        return $this->processesQuery()->where('status', 'awaiting-approval')->count();
    }

    private function getApprovedProcesses(): int
    {
        return $this->processesQuery()->where('status', 'approved')->count();
    }

    private function getFailedProcesses(): int
    {
        return $this->processesQuery()->where('status', 'failed')->count();
    }

    private function getAverageApprovalTime(): string
    {
        $user = Auth::user();

        $query = DB::table('processes')->join('process_signers', function ($join) {
            $join->on('processes.id', '=', 'process_signers.process_id')->where('process_signers.status', 'signed');
        })->where('processes.status', 'approved');

        if ($user->role_hash === hmac_hash('customer')) {
            $query->whereIn('processes.owner_id', $user->internalUsers()->pluck('users.id')->push($user->id));
        } else {
            $query->where('processes.owner_id', $user->id);
        }

        $averageSeconds = $query->selectRaw('AVG(TIMESTAMPDIFF(SECOND, processes.created_at, process_signers.action_at)) as average')->value('average');

        return $averageSeconds ? CarbonInterval::seconds((int) $averageSeconds)->cascade()->forHumans(short: true) : '--';
    }

    private function processesQuery(): Builder
    {
        $user = Auth::user();

        $query = Process::query();

        if ($user->role_hash === hmac_hash('customer')) {
            $ownerIds = $user->internalUsers()
                ->pluck('users.id')
                ->push($user->id);

            $query->whereIn('owner_id', $ownerIds);
        } else {
            $query->ownedBy($user->id);
        }

        return $query;
    }
};
?>

<div class="flex-1 flex flex-col">

    @if (session('success'))
    <div class="alert alert-success flex items-center justify-between mb-3">
        <div class="flex items-start gap-2">
            <div class="alert-icon"><i class="las la-check-circle"></i></div>
            <div class="alert-content leading-normal">{{ session('success') }}</div>
        </div>
    </div>
    @endif

    {{-- CABEÇALHO --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 mb-3">
        <div class="flex items-center gap-4">
            <h3 class="text-sm md:text-lg font-semibold tracking-wide uppercase text-text-soft">{{ $pageTitle }}</h3>
        </div>
        <div class="flex items-center justify-between gap-3">
            <a href="#" @click.prevent class="flex-1 md:w-auto h-full inline-flex items-center justify-center gap-1.5 rounded-md px-6 py-3 bg-primary text-text-soft">
                <i class="las la-file-csv text-xl"></i>
                <span class="text-sm">Exportar</span>
            </a>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">

        <div class="group relative overflow-hidden rounded-md border border-border bg-linear-to-br from-card to-card/80 p-6 transition-all duration-200 hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5">
            <div class="absolute inset-x-0 top-0 h-1 bg-primary"></div>

            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-text-muted">
                        Total de processos
                    </p>

                    <h2 class="mt-3 text-4xl font-bold leading-none text-text">
                        {{ number_format($statistics['total']) }}
                    </h2>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-primary/10 ring-1 ring-primary/10 transition group-hover:bg-primary/15">
                    <i class="las la-file-alt text-4xl text-primary"></i>
                </div>
            </div>
        </div>

        <div class="group relative overflow-hidden rounded-md border border-border bg-linear-to-br from-card to-card/80 p-6 transition-all duration-200 hover:border-warning/30 hover:shadow-lg hover:shadow-warning/5">
            <div class="absolute inset-x-0 top-0 h-1 bg-warning"></div>

            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-text-muted">
                        Pendentes
                    </p>

                    <h2 class="mt-3 text-4xl font-bold leading-none text-text">
                        {{ number_format($statistics['pending']) }}
                    </h2>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-warning/10 ring-1 ring-warning/10 transition group-hover:bg-warning/15">
                    <i class="las la-clock text-4xl text-warning"></i>
                </div>
            </div>
        </div>

        <div class="group relative overflow-hidden rounded-md border border-border bg-linear-to-br from-card to-card/80 p-6 transition-all duration-200 hover:border-info/30 hover:shadow-lg hover:shadow-info/5">
            <div class="absolute inset-x-0 top-0 h-1 bg-info"></div>

            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-text-muted">
                        Em aprovação
                    </p>

                    <h2 class="mt-3 text-4xl font-bold leading-none text-text">
                        {{ number_format($statistics['awaitingApproval']) }}
                    </h2>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-info/10 ring-1 ring-info/10 transition group-hover:bg-info/15">
                    <i class="las la-hourglass-half text-4xl text-info"></i>
                </div>
            </div>
        </div>

        <div class="group relative overflow-hidden rounded-md border border-border bg-linear-to-br from-card to-card/80 p-6 transition-all duration-200 hover:border-success/30 hover:shadow-lg hover:shadow-success/5">
            <div class="absolute inset-x-0 top-0 h-1 bg-success"></div>

            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-text-muted">
                        Aprovados
                    </p>

                    <h2 class="mt-3 text-4xl font-bold leading-none text-text">
                        {{ number_format($statistics['approved']) }}
                    </h2>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-success/10 ring-1 ring-success/10 transition group-hover:bg-success/15">
                    <i class="las la-check-circle text-4xl text-success"></i>
                </div>
            </div>
        </div>

        <div class="group relative overflow-hidden rounded-md border border-border bg-linear-to-br from-card to-card/80 p-6 transition-all duration-200 hover:border-danger/30 hover:shadow-lg hover:shadow-danger/5">
            <div class="absolute inset-x-0 top-0 h-1 bg-danger"></div>

            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-text-muted">
                        Reprovados
                    </p>

                    <h2 class="mt-3 text-4xl font-bold leading-none text-text">
                        {{ number_format($statistics['failed']) }}
                    </h2>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-danger/10 ring-1 ring-danger/10 transition group-hover:bg-danger/15">
                    <i class="las la-times-circle text-4xl text-danger"></i>
                </div>
            </div>
        </div>

        <div class="group relative overflow-hidden rounded-md border border-border bg-linear-to-br from-card to-card/80 p-6 transition-all duration-200 hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5">
            <div class="absolute inset-x-0 top-0 h-1 bg-primary"></div>

            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-text-muted">
                        Tempo médio
                    </p>

                    <h2 class="mt-3 text-3xl font-bold leading-tight text-text">
                        {{ $statistics['average'] }}
                    </h2>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-md bg-primary/10 ring-1 ring-primary/10 transition group-hover:bg-primary/15">
                    <i class="las la-stopwatch text-4xl text-primary"></i>
                </div>
            </div>
        </div>

    </div>

</div>