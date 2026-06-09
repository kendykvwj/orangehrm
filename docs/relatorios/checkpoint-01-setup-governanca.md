# Checkpoint 01 — Setup e Governança

**Trabalho:** Revitalização de Sistemas Legados e Cultura DevOps — OrangeHRM
**Instituição:** UNICESUMAR — Centro Universitário de Curitiba
**Autores:** Gustavo Gabriel, Renato Mota, Kendy Valente — Curitiba, 2026

## 1.1 Seleção do sistema legado

Sistema escolhido: **OrangeHRM Starter Application**, aplicação web de gestão de
recursos humanos open-source. Possui repositório público, histórico de evolução,
módulos de RH, banco relacional e estrutura ampla — adequado para análise de
manutenção, engenharia reversa e reengenharia.

O repositório é objeto de **estudo e intervenção controlada**, não de reescrita
integral. Objetivo: recuperar entendimento arquitetural, identificar débitos
técnicos e planejar refatorações incrementais de baixo risco de regressão.

## 1.2 Escopo da intervenção

Módulo delimitado: **PIM (`orangehrmPimPlugin`)** — gerenciamento de informações
de colaboradores (cadastro, consulta, edição e persistência).

| Item | Definição adotada |
|---|---|
| Sistema | OrangeHRM Starter Application |
| Linguagem principal | PHP, com parte cliente em Vue/TypeScript |
| Domínio | Gestão de recursos humanos |
| Módulo analisado | `orangehrmPimPlugin` |
| Fluxo principal | Cadastro, consulta, edição e persistência de colaboradores |

## 1.3 Justificativa técnica da escolha

| Critério exigido | Atendimento no OrangeHRM |
|---|---|
| Código aberto | Repositório público e licença GPL. |
| Software real | Aplicação HRM usada como produto open-source. |
| Possibilidade de melhorias | Módulos extensos com controllers, services, DAOs, entidades e testes. |
| Engenharia reversa | Estrutura modular permite extração de classes, dependências e fluxos. |
| Compatibilidade com DevOps | Há Dockerfile, `phpunit.xml` e CI com GitHub Actions. |
| Domínio compatível | Gestão de colaboradores, dados pessoais, cargos, salários. |

## 1.4 Modelo de branching e governança de configuração

Modelo adotado: **GitFlow**.

| Branch | Finalidade |
|---|---|
| `main` | Versão estável da entrega acadêmica. Recebe apenas código validado. |
| `development` | Linha principal de desenvolvimento e documentação. Branches criadas a partir daqui. |
| `feature/reverse-engineering-pim` | Documentos, diagramas UML e relatório de engenharia reversa. |
| `feature/refactor-pim-services` | Plano e implementação de refatorações. |
| `hotfix/**` | Correções urgentes a partir da versão estável, com testes regressivos. |

Cada alteração fica associada a branch, commit e pull request, permitindo
demonstrar evolução incremental, comparação antes/depois, revisão de código e
bloqueios automatizados no pipeline.

## 1.5 Evidências e comandos iniciais

```bash
git clone https://github.com/orangehrm/orangehrm.git
cd orangehrm
git checkout -b development
git checkout -b feature/reverse-engineering-pim
mkdir -p docs/uml docs/relatorios docs/evidencias
```

Estrutura de documentação do trabalho:

```text
docs/
  uml/
    pim-class-diagram.puml
    pim-sequence-add-employee.puml
    pim-target-architecture.puml
  relatorios/
    checkpoint-01-setup-governanca.md
    checkpoint-02-engenharia-reversa.md
    checkpoint-03-plano-reengenharia.md
  evidencias/
    estrutura-pim.png
    execucao-comandos.png
    metricas-code-smells.png
```

## Governança aplicada neste repositório (fork)

- Esteira DevOps (pipeline PIM + Docker) integrada em `development`.
- Pipeline `.github/workflows/pim-pipeline.yml` dispara em `main`, `development`,
  `feature/**` e `hotfix/**`.
- Gates: (1) lint PHP-CS-Fixer + cliente Vue/TS, (2) PHPUnit testsuite Pim +
  grupo Import, (3) build da imagem Docker, (4) status consolidado.
