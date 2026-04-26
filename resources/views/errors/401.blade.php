@include('errors.layout', [
    'code' => '401',
    'title' => 'Autenticacao necessaria',
    'message' => 'Sua sessao nao possui autenticacao valida para acessar este recurso.',
    'actionLabel' => 'Ir para o login',
    'actionUrl' => route('login'),
])
