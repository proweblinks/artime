<section class="relative w-screen min-h-screen flex items-stretch overflow-hidden bg-white overflow-x-hidden">

    <?php echo $__env->make("partials/login-screen", ["name" => __("Sign In to Your Account")], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="flex flex-col justify-center flex-1 px-8 py-16 bg-blueGray-100 z-10" style="background-image: url(<?php echo e(theme_public_asset('images/pattern-light-big.svg')); ?>); background-position: center;">
        <form class="actionForm max-w-md mx-auto w-full" action="<?php echo e(module_url('do_login')); ?>" method="POST">
            <div class="show-on-mobile">
                <a class="mb-4 inline-block" href="<?php echo e(url('')); ?>">
                    <img class="h-10" src="<?php echo e(url( get_option("website_logo_brand_dark", asset('public/img/logo-brand-dark.png')) )); ?>" alt="">
                </a>
                <h2 class="mb-16 text-4xl md:text-4xl font-bold font-heading tracking-px-n leading-tight">
                    <?php echo e($name ?? __("Welcome Back")); ?>

                </h2>
            </div>
            <label class="block mb-4">
                <p class="mb-2 text-gray-700 font-semibold leading-normal"><?php echo e(__("Email or Username")); ?></p>
                <input type="text" id="username" name="username" class="px-4 py-3.5 w-full text-gray-400 font-medium placeholder-gray-400 bg-white outline-none border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300" placeholder="<?php echo e(__('Enter username or email address')); ?>">
            </label>
            <label class="block mb-5">
                <p class="mb-2 text-gray-700 font-semibold leading-normal"><?php echo e(__("Password")); ?></p>
                <input id="password" type="password" name="password" class="px-4 py-3.5 w-full text-gray-400 font-medium placeholder-gray-400 bg-white outline-none border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300" placeholder="<?php echo e(__('Enter your Password')); ?>">
            </label>
            <div class="mb-3">
                <?php echo Captcha::render(); ?>

            </div>

            <div class="flex flex-wrap justify-between mb-4">
                <div class="flex justify-between w-full items-center">
                    <div class="flex items-center">
                        <input class="w-4 h-4" id="remember" type="checkbox" name="remember" value="1">
                        <label class="ml-2 text-gray-700 font-medium" for="remember">
                            <span><?php echo e(__("Remember Me")); ?></span>
                        </label>
                    </div>

                    <a href="<?php echo e(url('auth/forgot-password')); ?>" class="text-indigo-600 hover:text-indigo-700 font-medium"><?php echo e(__("Forgot Password?")); ?></a>
                </div>
            </div>

            <div class="msg-error mb-2"></div>

            <button type="submit" class="mb-8 py-4 px-9 w-full text-white font-semibold border border-indigo-700 rounded-xl shadow-4xl focus:ring focus:ring-indigo-300 bg-indigo-600 hover:bg-indigo-700 transition ease-in-out duration-200">
                <?php echo e(__("Sign In")); ?>

            </button>
                <?php
                    $socials = [
                        'google' => [
                            'status' => get_option('auth_google_login_status', 0),
                            'url'    => url('auth/login/google'),
                            'icon'   => '<img src="'.theme_public_asset('images/google.png').'" class="size-6">',
                            'label'  => __("Continue with Google"),
                        ],
                        'facebook' => [
                            'status' => get_option('auth_facebook_login_status', 0),
                            'url'    => url('auth/login/facebook'),
                            'icon'   => '<i class="fa-brands fa-square-facebook text-2xl text-[#1877F2]"></i>',
                            'label'  => __("Continue with Facebook"),
                        ],
                        'x' => [
                            'status' => get_option('auth_x_login_status', 0),
                            'url'    => url('auth/login/x'),
                            'icon'   => '<i class="fab fa-x-twitter mr-2 text-2xl text-[#000]"></i>',
                            'label'  => __("Continue with X"),
                        ],
                    ];
                ?>
            <?php if(collect($socials)->where('status', 1)->count()): ?>
                <p class="mb-5 text-sm text-gray-500 font-medium text-center">
                    <?php echo e(__("Or continue with")); ?>

                </p>

                <div class="flex flex-wrap justify-center -m-2">
                    <?php $__currentLoopData = $socials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($s['status']): ?>
                            <a href="<?php echo e($s['url']); ?>" class="flex items-center justify-center p-4 bg-white hover:bg-gray-50 border rounded-lg transition ease-in-out duration-200 gap-2 w-full mb-3">
                                <?php echo $s['icon']; ?>

                                <span class="font-semibold leading-normal"><?php echo e($s['label']); ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </form>

        <!-- Switch to signup -->
        <?php if(get_option("auth_signup_page_status", 1)): ?>
            <p class="text-center pt-4">
                <?php echo e(__("Don't have an account?")); ?>

                <a href="<?php echo e(url('auth/signup')); ?>" class="text-indigo-600 hover:text-indigo-700 font-medium"><?php echo e(__("Sign up")); ?></a>
            </p>
        <?php endif; ?>
    </div>
</section><?php /**PATH /home/artime/public_html/resources/themes/guest/nova/resources/views/auth/login.blade.php ENDPATH**/ ?>