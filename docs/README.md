# Documentação — Revitalização do módulo PIM (OrangeHRM)

Trabalho UNICESUMAR: *Revitalização de Sistemas Legados e Cultura DevOps*.
Recorte: módulo **PIM** (`orangehrmPimPlugin`) — fluxo de cadastro de colaborador.

## Estrutura

```text
docs/
  uml/          diagramas PlantUML (estado atual + arquitetura alvo)
  relatorios/   relatórios por checkpoint
  evidencias/   capturas de tela (screenshots)
```

## Relatórios

- [Checkpoint 01 — Setup e Governança](relatorios/checkpoint-01-setup-governanca.md)
- [Checkpoint 02 — Engenharia Reversa](relatorios/checkpoint-02-engenharia-reversa.md)
- [Checkpoint 03 — Plano de Reengenharia](relatorios/checkpoint-03-plano-reengenharia.md)

## Diagramas UML

- `uml/pim-class-diagram.puml` — classes do estado atual (Figura 1).
- `uml/pim-sequence-add-employee.puml` — sequência do cadastro (Figura 2).
- `uml/pim-target-architecture.puml` — arquitetura alvo proposta (Figura 3).

Renderizar com PlantUML:

```bash
plantuml docs/uml/*.puml
```

## GitFlow

`main` (estável) ← `development` (integração) ← `feature/**`.
A documentação vive em `feature/reverse-engineering-pim`; as refatorações em
`feature/refactor-pim-services`.
