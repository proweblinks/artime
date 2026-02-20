<div x-data class="min-h-screen overflow-hidden">
    <div class="absolute inset-x-0 -top-3 -z-10 transform-gpu overflow-hidden px-36 blur-3xl" aria-hidden="true">
        <div class="min-h-[100vh] overflow-hidden pt-10 pb-10"
             style="background: 
                radial-gradient(circle at 20% 10%, var(--color-info) -200%, transparent 35%), 
                radial-gradient(circle at 70% 65%, var(--color-success) -200%, transparent 30%);">
        </div>
    </div>

    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="rounded-2xl shadow-xl p-8 text-center bg-base-100/90">

                <!-- Logo/Icon -->
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4
                    {{ $status ? 'bg-success/20' : 'bg-error/20' }}">
                    <i class="fas {{ $status ? 'fa-check-circle text-success' : 'fa-times-circle text-error' }} text-3xl"></i>
                </div>

                <!-- Title -->
                <h2 class="text-2xl font-bold text-base-content mb-2">
                    {{ $status ? __('Activation Successful!') : __('Activation Failed') }}
                </h2>

                <!-- Message -->
                <p class="text-base-content mt-2">
                    {{ $message ?? ($status
                        ? __('Your account has been activated. You can now login.')
                        : __('The activation link is invalid, expired or your account was already activated.')) }}
                </p>

                <!-- Action Button -->
                <div class="mt-8">
                    <a href="{{ url('auth/login') }}"
                       class="btn-accent mb-8 py-4 w-full justify-center text-base">
                        <i class="fa fa-arrow-left mr-2"></i>
                        {{ __("Back to Login") }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>