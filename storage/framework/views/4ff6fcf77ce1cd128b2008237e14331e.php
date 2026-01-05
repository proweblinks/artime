<section class="relative w-screen min-h-screen flex items-stretch overflow-hidden bg-white overflow-x-hidden">

    <?php echo $__env->make("partials/login-screen", ["name" => __("Create an account & get started.")], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="flex flex-col justify-center flex-1 px-8 py-16 bg-blueGray-100 z-10" style="background-image: url(<?php echo e(theme_public_asset('images/pattern-light-big.svg')); ?>); background-position: center;">
        <form class="actionForm max-w-md mx-auto w-full space-y-5" action="<?php echo e(module_url('do_signup')); ?>" method="POST">
        	<div class="show-on-mobile">
                <a class="mb-4 inline-block" href="<?php echo e(url('')); ?>">
                    <img class="h-10" src="<?php echo e(url( get_option("website_logo_brand_dark", asset('public/img/logo-brand-dark.png')) )); ?>" alt="">
                </a>
                <h2 class="mb-16 text-4xl md:text-4xl font-bold font-heading tracking-px-n leading-tight">
                    <?php echo e(__("Create an account & get started.")); ?>

                </h2>
            </div>
		    <!-- Full Name -->
		    <div>
		        <label for="fullname" class="block text-gray-700 font-semibold mb-2"><?php echo e(__("Full Name")); ?></label>
		        <input type="text" id="fullname" name="fullname"
		               class="input input-bordered input-lg w-full px-4 py-3.5 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 outline-none"
		               placeholder="<?php echo e(__('Enter your full name')); ?>" required>
		    </div>

		    <!-- Email -->
		    <div>
		        <label for="email" class="block text-gray-700 font-semibold mb-2"><?php echo e(__("Email Address")); ?></label>
		        <input type="email" id="email" name="email"
		               class="input input-bordered input-lg w-full px-4 py-3.5 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 outline-none"
		               placeholder="<?php echo e(__('Enter your email address')); ?>" required>
		    </div>

		    <!-- Username -->
		    <div>
		        <label for="username" class="block text-gray-700 font-semibold mb-2"><?php echo e(__("Username")); ?></label>
		        <input type="text" id="username" name="username"
		               class="input input-bordered input-lg w-full px-4 py-3.5 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 outline-none"
		               placeholder="<?php echo e(__('Choose a username')); ?>" required>
		    </div>

		    <!-- Password -->
		    <div>
		        <label for="password" class="block text-gray-700 font-semibold mb-2"><?php echo e(__("Password")); ?></label>
		        <input type="password" id="password" name="password"
		               class="input input-bordered input-lg w-full px-4 py-3.5 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 outline-none"
		               placeholder="<?php echo e(__('Enter your password')); ?>" required>
		    </div>

		    <!-- Confirm Password -->
		    <div>
		        <label for="password_confirmation" class="block text-gray-700 font-semibold mb-2"><?php echo e(__("Confirm Password")); ?></label>
		        <input type="password" id="password_confirmation" name="password_confirmation"
		               class="input input-bordered input-lg w-full px-4 py-3.5 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 outline-none"
		               placeholder="<?php echo e(__('Re-enter your password')); ?>" required>
		    </div>

		    <!-- Timezone -->
		    <div>
		        <label for="timezone" class="block text-gray-700 font-semibold mb-2"><?php echo e(__("Timezone")); ?></label>
		        <select id="timezone" name="timezone"
		                class="select select-bordered input-lg w-full px-4 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 outline-none"
		                required>
		            <option value=""><?php echo e(__("Select your timezone")); ?></option>
		            <?php $__currentLoopData = timezone_identifiers_list(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
		                <option value="<?php echo e($tz); ?>" <?php echo e(old('timezone') == $tz ? 'selected' : ''); ?>>
		                    <?php echo e($tz); ?>

		                </option>
		            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
		        </select>
		    </div>

		    <div class="mb-3">
                <?php echo Captcha::render(); ?>

            </div>

		    <div class="flex flex-wrap justify-between mb-4">
                <div class="w-full">
                    <div class="flex items-center">
                        <input class="w-4 h-4" id="accep_terms" name="accep_terms" type="checkbox" value="1" required>
                        <label class="ml-2 text-gray-700 font-medium" for="accep_terms" >
                            <span><?php echo e(__("I agree to the")); ?></span>
                            <a class="text-indigo-600 hover:text-indigo-700" href="<?php echo e(url('terms-of-service')); ?>"><?php echo e(__("Terms & Conditions")); ?></a>
                        </label>
                    </div>
                </div>
            </div>

		    <!-- Error Message -->
		    <div class="msg-error mb-2"></div>

		    <!-- Submit -->
		    <button type="submit"
		            class="mb-8 py-4 px-9 w-full text-white font-semibold border border-indigo-700 rounded-xl shadow-4xl focus:ring focus:ring-indigo-300 bg-indigo-600 hover:bg-indigo-700 transition ease-in-out duration-200">
		        <?php echo e(__("Sign Up")); ?>

		    </button>

		    <!-- Switch to Sign In -->
		    <p class="text-center text-base-content/80 pt-4">
		        <?php echo e(__("Already have an account?")); ?>

		        <a href="<?php echo e(url('auth/login')); ?>" class="text-indigo-600 hover:text-indigo-700 font-medium"><?php echo e(__("Sign in")); ?></a>
		    </p>
		</form>

    </div>
</section>
<?php /**PATH /home/artime/public_html/resources/themes/guest/nova/resources/views/auth/signup.blade.php ENDPATH**/ ?>