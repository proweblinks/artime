<?php
    $faqs = Home::getFaqs();
?>

<section class="relative pt-15 pb-28 bg-blueGray-50 overflow-hidden">
    <img class="absolute bottom-0 left-1/2 transform -translate-x-1/2" src="<?php echo e(theme_public_asset('images/faqs/gradient.svg')); ?>" alt="">
    <div class="relative z-10 container px-4 mx-auto">
        <div class="md:max-w-4xl mx-auto">
            <p class="mb-7 text-sm text-indigo-600 text-center font-semibold uppercase tracking-px">
                <?php echo e(__("Have any questions?")); ?>

            </p>
            <h2 class="mb-16 text-6xl md:text-8xl xl:text-10xl text-center font-bold font-heading tracking-px-n leading-none">
                <?php echo e(__("Frequently Asked Questions")); ?>

            </h2>
            <div class="mb-11 flex flex-wrap -m-1"
                 x-data="{ open: null }"
            >
                <?php $__currentLoopData = $faqs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="w-full p-1">
                        <a
                            href="#"
                            x-on:click.prevent="open === <?php echo e($faq->id); ?> ? open = null : open = <?php echo e($faq->id); ?>"
                        >
                            <div :class="{ 'border-indigo-600': open === <?php echo e($faq->id); ?> }"
                                class="py-7 px-8 bg-white bg-opacity-60 border-2 border-gray-200 rounded-2xl shadow-10xl"
                            >
                                <div class="flex flex-wrap justify-between -m-2">
                                    <div class="flex-1 p-2">
                                        <h3 class="text-lg font-semibold leading-normal">
                                            <?php echo e($faq->title); ?>

                                        </h3>
                                        <div
                                            x-ref="container_<?php echo e($faq->id); ?>"
                                            :style="open === <?php echo e($faq->id); ?> ? 'height: ' + $refs['container_<?php echo e($faq->id); ?>'].scrollHeight + 'px' : ''"
                                            class="overflow-hidden h-0 duration-500"
                                        >
                                            <p class="mt-4 text-gray-600 font-medium">
                                                <?php echo $faq->content; ?>

                                            </p>
                                        </div>
                                    </div>
                                    <div class="w-auto p-2">
                                        <div :class="{ 'hidden': open === <?php echo e($faq->id); ?> }">
                                            <!-- chevron down -->
                                            <svg class="relative top-1" width="18" height="18" viewbox="0 0 18 18" fill="none"><path d="M14.25 6.75L9 12L3.75 6.75" stroke="#18181B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                        <div :class="{ 'hidden': open !== <?php echo e($faq->id); ?> }" class="hidden">
                                            <!-- chevron up -->
                                            <svg class="relative top-1" width="20" height="20" viewbox="0 0 20 20" fill="none"><path d="M4.16732 12.5L10.0007 6.66667L15.834 12.5" stroke="#4F46E5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <p class="text-gray-600 text-center font-medium">
                <span><?php echo e(__("Still have any questions?")); ?></span>
                <a class="font-semibold text-indigo-600 hover:text-indigo-700" href="<?php echo e(url('contact')); ?>"><?php echo e(__("Contact us")); ?></a>
            </p>
        </div>
    </div>
</section><?php /**PATH /home/artime/public_html/resources/themes/guest/nova/resources/views/partials/faqs.blade.php ENDPATH**/ ?>