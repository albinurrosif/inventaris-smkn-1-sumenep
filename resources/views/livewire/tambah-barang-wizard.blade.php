<div>
    {{-- Header Section (Breadcrumb, dll.) --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Tambah Jenis Barang Baru (Wizard)</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('barang.index') }}">Daftar Jenis Barang</a></li>
                        <li class="breadcrumb-item active">Tambah Wizard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-pills nav-justified twitter-bs-wizard-nav">
                @if ($this->stepNames()->count() > 0)
                    @foreach ($this->steps() as $stepClassString)
                        {{-- Loop dari definisi step --}}
                        @php
                            // Dapatkan alias dan info dari instance sementara
                            // Ini diperlukan karena $this->steps() mengembalikan class string
                            $stepInstance = app($stepClassString);
                            $stepAlias = app(\Livewire\Mechanisms\ComponentRegistry::class)->getName($stepClassString);
                            $stepInfo = $stepInstance->stepInfo();
                            $stepTitleForNav = $stepInfo['label'] ?? Str::title(str_replace('-', ' ', $stepAlias));
                            $stepIconForNav = $stepInfo['icon'] ?? 'bx bx-circle';
                        @endphp
                        <li class="nav-item" style="width: {{ 100 / count($this->steps()) }}%;">
                            <a class="nav-link {{ $this->currentStepName === $stepAlias ? 'active' : '' }}
                               {{-- Untuk isStepCompleted dan isStepAccessible, kita perlu logika sendiri atau mengandalkan UI dasar --}}
                               {{-- Spatie v2 tidak menyediakan ini semudah v3 via $this->state() di view --}}
                               {{-- Untuk isCompleted, kita bisa cek apakah index < currentIndex --}}
                               @php
$isCompleted = $this->stepNames()->search($stepAlias) < $this->stepNames()->search($this->currentStepName);
                                   // Untuk isAccessible, v2 biasanya mengizinkan kembali ke step yang sudah completed
                                   $isAccessible = $isCompleted || $this->currentStepName === $stepAlias; @endphp
                               {{ $isCompleted ? 'text-success' : '' }}
                               {{ !$isAccessible ? 'disabled' : '' }}"
                                href="#"
                                @if ($isAccessible && $this->currentStepName !== $stepAlias) wire:click="showStep('{{ $stepAlias }}')" @endif
                                aria-current="{{ $this->currentStepName === $stepAlias ? 'page' : 'false' }}"
                                aria-label="{{ $stepTitleForNav }}"
                                style="{{ !$isAccessible ? 'pointer-events: none; opacity: 0.65;' : '' }}">
                                <div class="step-icon" data-bs-toggle="tooltip" title="{{ $stepTitleForNav }}">
                                    <i class="{{ $stepIconForNav }}"></i>
                                </div>
                                <span class="step-title">{{ $stepTitleForNav }}</span>
                            </a>
                        </li>
                    @endforeach
                @else
                    <li class="nav-item" style="width: 100%;"><span class="nav-link text-muted">Memuat
                            navigasi...</span></li>
                @endif
            </ul>
        </div>

        <div class="card-body">
            {{-- Progress Bar --}}
            @if ($this->stepNames()->count() > 1)
                @php
                    $currentIndex = $this->stepNames()->search($this->currentStepName) ?? 0;
                    $totalSteps = $this->stepNames()->count();
                    $progressPercentage = $totalSteps > 1 ? ($currentIndex / ($totalSteps - 1)) * 100 : 0;
                @endphp
                <div class="progress mb-3" style="height: 5px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $progressPercentage }}%"
                        aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            @endif

            {{-- Current Step Content --}}
            <div>
                @if ($this->currentStepName)
                    @livewire($this->currentStepName, $this->getCurrentStepState($this->currentStepName), key($this->currentStepName . '-' . now()->timestamp))
                @else
                    <p class="text-muted text-center">Memuat langkah wizard...</p>
                @endif
            </div>
        </div>

        <div
            class="card-footer bg-light d-flex {{ $this->isFirstStep() ? 'justify-content-end' : 'justify-content-between' }} align-items-center">
            @if (!$this->isFirstStep())
                <button class="btn btn-light" wire:click="previousStep" wire:loading.attr="disabled">
                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                </button>
            @endif

            @if (!$this->isLastStep())
                <button class="btn btn-primary" wire:click="nextStep" wire:loading.attr="disabled"
                    wire:loading.class.remove="btn-primary" wire:loading.class="btn-secondary">
                    <span wire:loading wire:target="nextStep" class="spinner-border spinner-border-sm me-1"
                        role="status" aria-hidden="true"></span>
                    <span wire:loading.remove wire:target="nextStep">Lanjutkan</span>
                    <span wire:loading wire:target="nextStep">Memproses...</span>
                    <i wire:loading.remove wire:target="nextStep" class="mdi mdi-arrow-right ms-1"></i>
                </button>
            @else
                <button class="btn btn-success" wire:click="submitWizard" wire:loading.attr="disabled"
                    wire:loading.class.remove="btn-success" wire:loading.class="btn-secondary">
                    <span wire:loading wire:target="submitWizard" class="spinner-border spinner-border-sm me-1"
                        role="status" aria-hidden="true"></span>
                    <span wire:loading.remove wire:target="submitWizard">Simpan & Selesaikan</span>
                    <span wire:loading wire:target="submitWizard">Menyimpan...</span>
                    <i wire:loading.remove wire:target="submitWizard" class="mdi mdi-check ms-1"></i>
                </button>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success_wizard'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show" x-transition
            class="alert alert-success mt-3 fixed-bottom-right m-3 shadow-lg"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
            <i class="mdi mdi-check-circle-outline me-2"></i> {{ session('success_wizard') }}
        </div>
    @endif
    @if (session()->has('error_wizard'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 8000)" x-show="show" x-transition
            class="alert alert-danger mt-3 fixed-bottom-right m-3 shadow-lg"
            style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
            <i class="mdi mdi-alert-circle-outline me-2"></i> {{ session('error_wizard') }}
        </div>
    @endif
</div>
