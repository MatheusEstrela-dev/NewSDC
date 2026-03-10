<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SDC - Redefinir Senha</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/fontawesome-free/css/all.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/admin-lte/adminlte.min.css')); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #64a2ff 0%, #0d5ee0 100%);
            background-repeat: no-repeat;
            background-size: cover;
            min-height: 100vh;
            font-family: 'Source Sans Pro', sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.95);
            border: none;
            max-width: 400px;
            margin: 2rem auto;
        }

        .card-header {
            background: linear-gradient(90deg, #0056b3 0%, #007bff 100%);
            color: #fff;
            border-radius: 16px 16px 0 0;
            padding: 1.5rem 1rem;
        }

        .card-body {
            padding: 2rem 1.5rem 1.5rem 1.5rem;
        }

        .form-control {
            border-radius: 8px;
            font-size: 1rem;
        }

        .input-group-text {
            border-radius: 0 8px 8px 0;
            background: #f1f1f1;
        }

        .btn-primary {
            border-radius: 8px;
            font-weight: 600;
            background: linear-gradient(90deg, #0056b3 0%, #007bff 100%);
            border: none;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <img src="<?php echo e(asset('imagem/logo_modelo_1-160X44-a.png')); ?>" alt="Logo SDC" style="max-width: 100%; height: 100%;">
            </div>
            <div class="card-body">
                <p class="login-box-msg">Você está a apenas um passo de sua nova senha, recupere agora.</p>

                <form action="<?php echo e(route('password.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">

                    <?php
                        $maskedEmail = '';
                        $emailValue = old('email', $email);
                        if ($emailValue) {
                            $parts = explode('@', $emailValue);
                            if (count($parts) === 2) {
                                $name = $parts[0];
                                $domain = $parts[1];
                                $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 3));
                                $maskedEmail = $maskedName . '@' . $domain;
                            }
                        }
                    ?>
                    <input type="hidden" name="email" value="<?php echo e($emailValue); ?>">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="<?php echo e($maskedEmail); ?>" placeholder="Email" readonly disabled>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback d-block" role="alert">
                                <strong><?php echo e($message); ?></strong>
                            </span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Nova Senha" autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <span class="invalid-feedback" role="alert">
                                <strong><?php echo e($message); ?></strong>
                            </span>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Confirme a Nova Senha">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Alterar senha</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="<?php echo e(asset('vendor/jquery/jquery.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
</body>

</html>
<?php /**PATH /var/www/resources/views/auth/reset-password.blade.php ENDPATH**/ ?>