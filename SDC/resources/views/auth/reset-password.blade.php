<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SDC - Redefinir Senha</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/admin-lte/adminlte.min.css') }}">
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
                <img src="{{ asset('imagem/logo_modelo_1-160X44-a.png') }}" alt="Logo SDC" style="max-width: 100%; height: 100%;">
            </div>
            <div class="card-body">
                <p class="login-box-msg">Você está a apenas um passo de sua nova senha, recupere agora.</p>

                <form action="{{ route('password.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    @php
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
                    @endphp
                    <input type="hidden" name="email" value="{{ $emailValue }}">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="{{ $maskedEmail }}" placeholder="Email" readonly disabled>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                        @error('email')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Nova Senha" autofocus>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
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
    
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
