<div class="min-h-screen py-12 px-4 bg-blueGray-50 py-24">
    <div class="max-w-4xl mx-auto">
        <!-- Header Section -->
        <div class="mb-8 text-center">
            <h1 class="text-6xl md:text-7xl font-bold font-heading leading-none text-gray-900 mb-4"><?php echo e(__("Terms & Conditions")); ?></h1>
            <p class="text-lg text-gray-600 font-medium"><?php echo e(__('Please read these Terms & Conditions carefully before using this service.')); ?></p>
        </div>
        <!-- Main Content -->
        <div class="bg-white rounded-4xl shadow-sm p-8">
            <div class="prose prose-content prose-lg max-w-none">
                <?php echo htmlspecialchars_decode(get_option('terms_of_use')); ?>

            </div>
        </div>
    </div>
</div><?php /**PATH /home/artime/public_html/resources/themes/guest/nova/resources/views/pages/terms_of_service.blade.php ENDPATH**/ ?>