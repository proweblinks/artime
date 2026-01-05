<?php
    $pricing = \Pricing::plansWithFeatures();
    $planTypes = \Modules\AdminPlans\Facades\Plan::getTypes();
    $minCol = 3;
?>

<section x-data="{ type: <?php echo e(array_key_first($planTypes)); ?> }" class="pt-24 pb-32 bg-blueGray-50 overflow-hidden relative z-20">
    <div class="container px-4 mx-auto mb-10">
        <h2 class="mb-6 text-6xl md:text-8xl xl:text-10xl font-bold font-heading tracking-px-n leading-none">
            <?php echo e(__("Pricing")); ?>

        </h2>
        <div class="mb-16 flex flex-wrap justify-between -m-4">
            <div class="w-auto p-4">
                <div class="md:max-w-md">
                    <p class="text-lg text-gray-900 font-medium leading-relaxed">
                        <?php echo e(__("Choose an affordable plan packed with top features to engage your audience, create loyalty, and boost sales.")); ?>

                    </p>
                </div>
            </div>
            
            <div class="w-auto p-4">
                <div class="inline-flex items-center max-w-max gap-2">
                    <?php $__currentLoopData = $planTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeKey => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button 
                            type="button"
                            class="px-4 py-1 mx-1 rounded-full font-semibold transition text-gray-600"
                            :class="type == <?php echo e($typeKey); ?> ? 'bg-indigo-600 text-white' : 'bg-gray-200 hover:bg-indigo-100'"
                            x-on:click="type=<?php echo e($typeKey); ?>"
                        >
                            <?php echo e(__($typeLabel)); ?>

                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
        <div class="border border-blueGray-200 rounded-3xl bg-white bg-opacity-90">
            <div class="flex flex-wrap md:divide-x divide-blueGray-200">
                <?php $__currentLoopData = $planTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeKey => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $plans = $pricing[$typeKey] ?? [];
                        $planCount = count($plans);
                    ?>

                    <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                        <?php
                            $isFreePlan = $plan['free_plan'];
                        ?>

                        <div class="w-full xs:w-full sm:w-full md:w-full lg:w-1/3 flex-1"
                             x-show="type == <?php echo e($typeKey); ?>"
                             x-transition
                             style="display: none; z-index: <?php echo e(1000 - $index); ?>">

                            <div class="relative px-9 pt-8 pb-11 h-full rounded-3xl" style="backdrop-filter: blur(46px);">

                                
                                <?php if(!empty($plan['featured'])): ?>
                                    <div class="overflow-hidden absolute right-0 w-40 h-40 top-0">
                                        <div class="absolute top-6 -right-10 rotate-45">
                                            <span class="bg-indigo-600 text-white px-12 py-1 text-xs font-bold shadow-md uppercase">
                                                <?php echo e(__('Featured')); ?>

                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <span class="mb-3 inline-block text-sm text-indigo-600 font-semibold uppercase tracking-px leading-snug">
                                    <?php echo e(__($plan['name'] ?? '-')); ?>

                                </span>
                                <p class="mb-6 text-gray-500 font-medium leading-relaxed">
                                    <?php echo e(__($plan['desc'] ?? '')); ?>

                                </p>
                                <h3 class="mb-1 text-4xl text-gray-900 font-bold leading-tight">
                                    <?php if($isFreePlan): ?>
                                        <?php echo e(price(0)); ?>

                                    <?php else: ?>
                                        <?php echo e(price($plan['price'] ?? 0)); ?>

                                    <?php endif; ?>
                                    <span class="text-gray-400">/<?php echo e(strtolower($typeLabel)); ?></span>
                                </h3>
                                <p class="mb-8 text-sm text-gray-500 font-medium leading-relaxed">
                                    <?php echo e(__("Billed")); ?> <?php echo e($typeLabel); ?>

                                </p>

                                <?php if($isFreePlan): ?>
                                    <a href="<?php echo e(route('payment.index', $plan['id_secure'])); ?>" class="mb-9 py-4 px-9 w-full font-semibold rounded-xl text-indigo-600 bg-white hover:bg-indigo-200 border border-indigo-600 hover:text-white transition ease-in-out duration-200 text-center block">
	                                    <?php echo e(__("Start for Free")); ?>

	                                </a>
                                <?php else: ?>
                                    <a href="<?php echo e(route('payment.index', $plan['id_secure'])); ?>" class="mb-9 py-4 px-9 w-full font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition ease-in-out duration-200 text-center block">
	                                    <?php echo e(__("Choose Plan")); ?>

	                                </a>
                                <?php endif; ?>
                                <ul>
                                    <?php $__currentLoopData = $plan['features'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="mb-4 flex items-center gap-2">
                                            <i class="fa-regular fa-check <?php echo e($feature['check'] ? 'text-green-600' : 'text-gray-500'); ?>"></i>
                                            <p class="font-semibold leading-normal"><?php echo e(__($feature['label'] ?? $feature)); ?></p>

                                            <?php if(!empty($feature['subfeature'])): ?>
                                                <div x-data="{ open: false, timer: null }" class="relative ml-2">
                                                    <div
                                                        @mouseenter="clearTimeout(timer); open = true"
                                                        @mouseleave="timer = setTimeout(() => open = false, 120)"
                                                        class="w-5 h-5 flex items-center justify-center rounded-full bg-indigo-200 text-xs hover:bg-indigo-400 transition cursor-pointer z-20 relative"
                                                    ><i class="fa-light fa-info"></i></div>
                                                    <div
                                                        x-show="open"
                                                        @mouseenter="clearTimeout(timer); open = true"
                                                        @mouseleave="timer = setTimeout(() => open = false, 120)"
                                                        class="absolute left-full top-1/2 ml-3 -translate-y-1/3 z-800 min-w-60 max-h-[400px] overflow-y-auto rounded-lg border border-white/10 bg-white text-gray-800 p-4 shadow-xl"
                                                        x-transition
                                                    >
                                                        <?php $__currentLoopData = $feature['subfeature']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tabGroup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <div class="mb-5 last:mb-0">
                                                                <div class="font-semibold text-sm text-gray-900 mb-3 text-left">
                                                                    <?php echo e(__($tabGroup['tab_name'])); ?>

                                                                </div>
                                                                <ul class="text-sm space-y-1 text-left">
                                                                    <?php $__currentLoopData = $tabGroup['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                        <li class="flex items-center gap-1.5 py-1">
                                                                            <?php if($sub['check']): ?>
                                                                                <span class="w-5 h-5 flex items-center justify-center rounded-full bg-success/20 text-xs font-semibold">
                                                                                  <i class="fa-solid fa-check"></i>
                                                                                </span>
                                                                            <?php else: ?>
                                                                                <span class="w-5 h-5 flex items-center justify-center rounded-full bg-error/20 text-xs font-semibold">
                                                                                  <i class="fa-solid fa-xmark"></i>
                                                                                </span>
                                                                            <?php endif; ?>
                                                                            <span><?php echo e(__($sub['label'])); ?></span>
                                                                        </li>
                                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                </ul>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php for($i = $planCount; $i < $minCol; $i++): ?>
                        <div class="w-full md:w-1/2 lg:w-1/3 flex-1"
                             x-show="type == <?php echo e($typeKey); ?>"
                             style="display: none;"></div>
                    <?php endfor; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
    <p class="mb-4 text-sm text-gray-500 text-center font-medium leading-relaxed">
        <?php echo e(__("Trusted by secure payment service")); ?>

    </p>
    <div class="flex flex-wrap gap-2 justify-center">
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="<?php echo e(theme_public_asset('logos/brands/stripe.svg')); ?>" alt="Stripe">
            </a>
        </div>
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="<?php echo e(theme_public_asset('logos/brands/amex.svg')); ?>" alt="Amex">
            </a>
        </div>
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="<?php echo e(theme_public_asset('logos/brands/mastercard.svg')); ?>" alt="Mastercard">
            </a>
        </div>
        <div class="w-auto">
            <img class="h-24" src="<?php echo e(theme_public_asset('logos/brands/paypal.svg')); ?>" alt="Paypal">
        </div>
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="<?php echo e(theme_public_asset('logos/brands/visa.svg')); ?>" alt="Visa">
            </a>
        </div>
        <div class="w-auto">
            <a href="#">
                <img class="h-24" src="<?php echo e(theme_public_asset('logos/brands/apple-pay.svg')); ?>" alt="Apple Pay">
            </a>
        </div>
    </div>
</section>
<?php /**PATH /home/artime/public_html/resources/themes/guest/nova/resources/views/partials/pricing.blade.php ENDPATH**/ ?>