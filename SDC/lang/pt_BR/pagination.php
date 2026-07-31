<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Linhas de idioma da paginacao
|--------------------------------------------------------------------------
|
| Sem este arquivo, trans('pagination.previous') devolve a propria chave, e
| qualquer tela que imprima os links do paginator (links()->render() no Blade ou
| v-html="link.label" no Vue) mostra "pagination.previous" para o usuario final.
| Foi exatamente o que acontecia na listagem de usuarios do Permissionamento.
|
| Depois da unificacao da paginacao, nenhuma tela depende mais destes rotulos --
| o componente Pagination.vue escreve "Anterior"/"Proxima" no proprio template.
| Este arquivo fica como defesa: se algum lugar voltar a renderizar os links
| crus, sai traduzido em vez de vazar chave.
|
*/

return [
    'previous' => '&laquo; Anterior',
    'next' => 'Próxima &raquo;',
];
