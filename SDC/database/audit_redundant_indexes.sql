-- Indices redundantes: candidatos a drop, com uso real.
--
-- Um indice cujas colunas sao prefixo exato de um indice mais largo do mesmo
-- tipo e logicamente redundante: o Postgres usa a coluna lider do composto
-- para qualquer filtro sobre ela. O ganho de dropar nao e espaco, e escrita --
-- todo INSERT/UPDATE mantem uma estrutura a menos, com menos bloat e VACUUM.
--
-- MAS o planner as vezes escolhe o indice estreito justamente por ser menor.
-- Rode isto em PRODUCAO, nao em dev: idx_scan de um banco de dev nao prova
-- nada. Confirme buscas = 0 depois de um ciclo representativo de uso (semana
-- fechada, rodada de relatorios, fechamento de mes) antes de dropar.
--
-- Comparacao por array de indkey, nao por texto: comparar indkey::text daria
-- falso positivo, porque attnum 4 e prefixo textual de 41.

with idx as (
    select i.indrelid::regclass                     as tabela,
           i.indexrelid::regclass                   as indice,
           (i.indkey::int2[])[0:i.indnkeyatts - 1]  as cols,
           i.indisunique                            as uniq,
           ic.relam                                 as metodo,
           pg_relation_size(i.indexrelid)           as bytes
    from pg_index i
    join pg_class c on c.oid = i.indrelid
    join pg_class ic on ic.oid = i.indexrelid
    join pg_namespace n on n.oid = c.relnamespace and n.nspname = 'public'
    where i.indisvalid
      and i.indpred is null      -- indice parcial nao e coberto por composto
      and i.indexprs is null     -- indice de expressao nao entra na comparacao
)
select a.tabela,
       a.indice                          as redundante,
       pg_size_pretty(a.bytes)           as tamanho,
       b.indice                          as coberto_por,
       s.idx_scan                        as buscas,
       case when s.idx_scan = 0 then 'candidato' else 'EM USO - nao dropar' end as veredito
from idx a
join idx b
  on  a.tabela = b.tabela
  and a.indice <> b.indice
  and a.metodo = b.metodo
  and array_length(b.cols, 1) > array_length(a.cols, 1)
  and b.cols[1:array_length(a.cols, 1)] = a.cols
left join pg_stat_user_indexes s on s.indexrelid = a.indice::oid
where not a.uniq              -- nunca dropar UNIQUE: e restricao, nao so indice
order by s.idx_scan nulls first, a.bytes desc;
