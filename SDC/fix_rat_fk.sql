-- Corrige as FK constraints de ocorrencia_id em todas as tabelas rat_relato_*
-- que apontavam erroneamente para rat_ocorrencia_relatos em vez de rat_ocorrencias

-- 1. Remove todas as FK constraints erradas de ocorrencia_id nas tabelas rat_relato_*
DO $$
DECLARE r RECORD;
BEGIN
    FOR r IN
        SELECT tc.constraint_name, tc.table_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu
            ON tc.constraint_name = kcu.constraint_name
            AND kcu.table_name = tc.table_name
        WHERE tc.constraint_type = 'FOREIGN KEY'
          AND kcu.column_name = 'ocorrencia_id'
          AND (tc.table_name LIKE 'rat_relato%' OR tc.table_name = 'rat_ocorrencia_historicos')
    LOOP
        EXECUTE format('ALTER TABLE %I DROP CONSTRAINT %I', r.table_name, r.constraint_name);
        RAISE NOTICE 'Removida constraint: % da tabela %', r.constraint_name, r.table_name;
    END LOOP;
END $$;

-- 2. Recria as FK constraints corretas apontando para rat_ocorrencias
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'rat_relato_dados_gerais') THEN
        ALTER TABLE rat_relato_dados_gerais
            ADD CONSTRAINT rat_relato_dados_gerais_ocorrencia_id_foreign
            FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE;
        RAISE NOTICE 'FK corrigida: rat_relato_dados_gerais';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'rat_relato_envolvidos') THEN
        ALTER TABLE rat_relato_envolvidos
            ADD CONSTRAINT rat_relato_envolvidos_ocorrencia_id_foreign
            FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE;
        RAISE NOTICE 'FK corrigida: rat_relato_envolvidos';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'rat_relato_recursos') THEN
        ALTER TABLE rat_relato_recursos
            ADD CONSTRAINT rat_relato_recursos_ocorrencia_id_foreign
            FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE;
        RAISE NOTICE 'FK corrigida: rat_relato_recursos';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'rat_relato_vistoria') THEN
        ALTER TABLE rat_relato_vistoria
            ADD CONSTRAINT rat_relato_vistoria_ocorrencia_id_foreign
            FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE;
        RAISE NOTICE 'FK corrigida: rat_relato_vistoria';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'rat_relato_vistorias') THEN
        ALTER TABLE rat_relato_vistorias
            ADD CONSTRAINT rat_relato_vistorias_ocorrencia_id_foreign
            FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE;
        RAISE NOTICE 'FK corrigida: rat_relato_vistorias';
    END IF;

    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'rat_ocorrencia_historicos') THEN
        ALTER TABLE rat_ocorrencia_historicos
            ADD CONSTRAINT rat_ocorrencia_historicos_ocorrencia_id_foreign
            FOREIGN KEY (ocorrencia_id) REFERENCES rat_ocorrencias(id) ON DELETE CASCADE;
        RAISE NOTICE 'FK corrigida: rat_ocorrencia_historicos';
    END IF;
END $$;

-- Verificação: mostra tabela e para onde cada FK aponta
SELECT
    tc.table_name,
    tc.constraint_name,
    ccu.table_name AS references_table
FROM information_schema.table_constraints tc
JOIN information_schema.referential_constraints rc
    ON tc.constraint_name = rc.constraint_name
JOIN information_schema.constraint_column_usage ccu
    ON rc.unique_constraint_name = ccu.constraint_name
JOIN information_schema.key_column_usage kcu
    ON tc.constraint_name = kcu.constraint_name
WHERE kcu.column_name = 'ocorrencia_id'
  AND (tc.table_name LIKE 'rat_relato%' OR tc.table_name = 'rat_ocorrencia_historicos')
ORDER BY tc.table_name;
