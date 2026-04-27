@include('errors.layout', [
    'code' => '401',
    'title' => 'Autenticação necessária',
    'message' => 'Sua sessão não possui autenticação válida para acessar este recurso.',
    'actionLabel' => 'Ir para o login',
    'actionUrl' => route('login'),
])
