<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SDC - Sistema de Defesa Civil MG</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{asset('vendor/fontawesome-free/css/all.min.css')}}">
    <link rel="stylesheet" href="{{asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('vendor/admin-lte/adminlte.min.css')}}">
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
            background: rgba(255,255,255,0.95);
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
        .card-header .h1 {
            font-weight: 700;
            font-size: 1.7rem;
            letter-spacing: 1px;
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
        .icheck-primary label {
            font-weight: 400;
            font-size: 0.95rem;
        }
        .alert-danger {
            border-radius: 8px;
            font-size: 0.95rem;
        }
        .support-info {
            font-size: 0.95rem;
            color: #333;
        }
        .support-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0.5rem 0 0 0;
            color: #0056b3;
        }
        @media (max-width: 576px) {
            .card {
                max-width: 95vw;
                margin: 1rem;
            }
            .card-header, .card-body {
                padding: 1rem;
            }
        }
        .forgot-password {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #0056b3;
            background: transparent;
            border: none;
            font-size: 0.95rem;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.15s, color 0.15s, transform 0.06s;
        }
        .forgot-password .fa-question-circle {
            font-size: 0.95rem;
            color: #007bff;
        }
        .forgot-password:hover {
            color: #003f8a;
            text-decoration: none;
            transform: translateY(-1px);
        }
        .forgot-password:active {
            transform: translateY(0);
        }
        .forgot-password.small-muted {
            font-size: 0.9rem;
            color: #4b5563;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                {{-- <img src="{{ asset('imagem/logo_modelo_1-160X44-a.png') }}" alt="Logo SDC" style="max-width: 160px; height: 44px; margin-bottom: 10px;"> --}}
                <img src="{{ asset('imagem/logo_modelo_1-160X44-a.png') }}" alt="Logo SDC" style="max-width: 100%; height: 100%;">
                {{-- <b>SDC <br> Sistema de Defesa Civil</b> --}}
            </div>
            <div class="card-body">
                <form action="{{ url('login') }}" method="POST">
                    @csrf

                    {{-- CPF field --}}
                    <div class="input-group mb-3">
                        <input type="text" name="cpf" class="form-control @error('cpf') is-invalid @enderror" placeholder="CPF - Somente números" maxlength="11" value="">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-id-card"></span>
                            </div>
                        </div>
                        @error('cpf')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Password field --}}
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Digite sua Senha" value="">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock pl-1"></span>
                            </div>
                        </div>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-7">
                            <div class="icheck-primary" title="{{ __('adminlte::adminlte.remember_me_hint') }}">
                                <input type="checkbox" name="remember" id="remember"
                                    {{ old('remember') ? 'checked' : '' }}>
                                <label for="remember">Lembre-me</label>
                            </div>
                        </div>
                        <div class="col-5">
                            <button type="submit"
                                class="btn btn-primary btn-block">
                                <span class="fas fa-sign-in-alt"></span> Entrar
                            </button>
                        </div>
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-7">
                            <div class="small text-muted mt-1">
                                <a href="{{ route('password.request') }}" class="forgot-password" aria-label="Esqueci minha senha">
                                    <span class="fas fa-question-circle" aria-hidden="true"></span>
                                    <span>Esqueci minha senha</span>
                                </a>
                            </div>
                        </div>
                        <div class="col-5 text-right">
                        </div>
                    </div>
                </form>

                @isset($message)
                <span class="alert alert-danger p-2 d-block mb-2" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @endisset

                <div class="support-info mt-3 text-center">
                    Suporte:<br>
                    <h4>sdc@defesacivil.mg.gov.br</h4>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>

</html>
