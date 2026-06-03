# PAE Aba Anexos

## Summary

Implementar a aba **Anexos** no formulario PAE do novo SDC, posicionada entre **Apontamentos Tecnicos** e **Conclusao**.

O fluxo segue a arquitetura:

`Request -> DTO -> Controller -> Service -> Model`

## Backend

- Criar tabela `pae_form_anexos` para guardar metadados e endereco do arquivo no storage.
- Usar disk privado `pae`, apontando para `storage/app/pae`.
- Salvar arquivos em `formularios/{pae_form_id}`.
- Aceitar apenas `pdf`, `jpg`, `jpeg` e `png`, com limite de 10 MB.
- Expor rotas para upload, download e remocao.
- Bloquear download/remocao quando o anexo nao pertencer ao formulario informado.

## Frontend

- Inserir aba **Anexos** como etapa 4.
- Mover **Conclusao** para etapa 5.
- Bloquear upload enquanto o formulario ainda nao tiver ID.
- Enviar upload como `multipart/form-data`.
- Listar anexos com nome, tamanho, descricao e acoes de baixar/remover.

## Tests

- Upload valido de PDF e imagem.
- Rejeicao de extensao invalida.
- Rejeicao de arquivo acima de 10 MB.
- Remocao apaga registro e arquivo fisico.
- Download retorna arquivo correto.
- Download/remocao de anexo de outro formulario retorna 404.
