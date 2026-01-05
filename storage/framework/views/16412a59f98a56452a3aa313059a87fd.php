<?php $__env->startSection('pagetitle', __("Forgot Password")); ?>
<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('auth.forgot_password', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/artime/public_html/modules/Auth/resources/views/forgot_password.blade.php ENDPATH**/ ?>