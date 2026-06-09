# Evidências

Capturas de tela usadas como evidência de governança e análise. Os arquivos
`.png` são screenshots manuais — substituir os placeholders abaixo pelas imagens
reais antes da entrega.

| Arquivo | Conteúdo esperado | Status |
|---|---|---|
| `estrutura-pim.png` | Árvore de diretórios do `orangehrmPimPlugin` (Controller, Dao, Dto, Service, entity, Api). | ⏳ pendente |
| `execucao-comandos.png` | Terminal com os comandos de setup/governança (clone, branches, mkdir). | ⏳ pendente |
| `metricas-code-smells.png` | Métricas/inspeção dos code smells (tamanho de classe, complexidade do DAO). | ⏳ pendente |

Sugestões de coleta:
- `estrutura-pim.png`: `tree src/plugins/orangehrmPimPlugin -L 2` ou print do explorer.
- `execucao-comandos.png`: print do histórico Git / saída de `git log --graph`.
- `metricas-code-smells.png`: saída de ferramenta de análise estática (ex.:
  PHPMD, PHPStan) ou contagem de linhas/métodos das classes citadas.
