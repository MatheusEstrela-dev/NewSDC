<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SDC - Sistema de Defesa Civil MG</title>

    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/admin-lte/adminlte.min.css') }}">
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-7GBFPM19TR"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-7GBFPM19TR');
</script>
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

            .card-header,
            .card-body {
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
                <form action="{{ route('password.email') }}" method="POST" id="reset-form">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="d-block">Como deseja redefinir a senha?</label>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-primary active" id="opt-cpf">
                                <input type="radio" name="reset_type" value="cpf" checked autocomplete="off"> Por CPF
                            </label>
                            <label class="btn btn-outline-primary" id="opt-municipio">
                                <input type="radio" name="reset_type" value="municipio" autocomplete="off"> Por Município
                            </label>
                        </div>
                    </div>

                    <div id="field-cpf">
                        <div class="small text-muted mt-1">
                            <input type="text" name="cpf" id="cpf" class="form-control mb-2" placeholder="CPF - Somente números" maxlength="11" value="{{ old('cpf') }}">
                        </div>
                    </div>

                    <div id="field-municipio" style="display:none;">
                        <div class="mb-3">
                            <select name="id_municipio" id="id_municipio" class="js-example-basic-single form-control">
                                <option value="">Selecione o município</option>
                                @foreach ($municipios ?? [] as $m)
                                    <option value="{{ $m->id }}">{{ $m->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <a href="{{ url('login') }}" class="back-to-login"><span class="fas fa-arrow-left mr-1"></span> Voltar ao login</a>
                        </div>
                        <div class="col-6 text-right">
                            <button type="submit" class="btn btn-primary"><span class="fas fa-envelope mr-1"></span> Redefinir senha</button>
                        </div>
                    </div>
                </form>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> {!! session('success') !!}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> {!! session('error') !!}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="support-info mt-3 text-center">
                    Suporte:<br>
                    <h4>sdc@defesacivil.mg.gov.br</h4>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2({
                theme: 'bootstrap4',
                width: 'resolve',
                placeholder: 'Selecione o município',
                allowClear: true
            });

            $("#opt-cpf").click(function() {
                $("#field-cpf").show();
                $("#field-municipio").hide();
                $("#opt-cpf").addClass("active");
                $("#opt-municipio").removeClass("active");
            });

            $("#opt-municipio").click(function() {
                $("#field-municipio").show();
                $("#field-cpf").hide();
                $("#opt-municipio").addClass("active");
                $("#opt-cpf").removeClass("active");
            });
        });
    </script>
</body>

</html>
