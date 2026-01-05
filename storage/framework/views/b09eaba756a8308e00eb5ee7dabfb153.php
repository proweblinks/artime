<?php if($paginator->hasPages()): ?>
    <?php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $showPages = 2;
    ?>
    <div class="flex justify-center items-center space-x-2 mt-8">

        
        <?php if($currentPage == 1): ?>
            <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </span>
        <?php else: ?>
            <a href="<?php echo e($paginator->url($currentPage - 1)); ?>"
               class="px-3 py-2 text-gray-500 hover:text-gray-700 transition-colors"
               aria-label="Previous">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
        <?php endif; ?>

        
        <?php if($currentPage > $showPages + 1): ?>
            <a href="<?php echo e($paginator->url(1)); ?>"
               class="px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                1
            </a>
            <?php if($currentPage > $showPages + 2): ?>
                <span class="px-4 py-2 text-gray-500">...</span>
            <?php endif; ?>
        <?php endif; ?>

        
        <?php for($i = max(1, $currentPage - $showPages); $i <= min($lastPage, $currentPage + $showPages); $i++): ?>
            <?php if($i == $currentPage): ?>
                <span class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg"><?php echo e($i); ?></span>
            <?php else: ?>
                <a href="<?php echo e($paginator->url($i)); ?>"
                   class="px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                    <?php echo e($i); ?>

                </a>
            <?php endif; ?>
        <?php endfor; ?>

        
        <?php if($currentPage < $lastPage - $showPages): ?>
            <?php if($currentPage < $lastPage - $showPages - 1): ?>
                <span class="px-4 py-2 text-gray-500">...</span>
            <?php endif; ?>
            <a href="<?php echo e($paginator->url($lastPage)); ?>"
               class="px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">
                <?php echo e($lastPage); ?>

            </a>
        <?php endif; ?>

        
        <?php if($currentPage == $lastPage): ?>
            <span class="px-3 py-2 text-gray-400 cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </span>
        <?php else: ?>
            <a href="<?php echo e($paginator->url($currentPage + 1)); ?>"
               class="px-3 py-2 text-gray-500 hover:text-gray-700 transition-colors"
               aria-label="Next">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?><?php /**PATH /home/artime/public_html/resources/themes/guest/nova/resources/views/partials/pagination.blade.php ENDPATH**/ ?>