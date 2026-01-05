
<?php $__env->startSection('pagetitle', __("Blogs")); ?>
<?php $__env->startSection('content'); ?>
  <?php echo $__env->make('pages.blogs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/artime/public_html/modules/Guest/resources/views/blogs.blade.php ENDPATH**/ ?>