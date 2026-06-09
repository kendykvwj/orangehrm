# Checkpoint 03 — Plano de Reengenharia

## 3.1 Objetivo e premissas

Transformar os achados da engenharia reversa em um roteiro de modernização
**incremental** do módulo PIM. Não reescrever o módulo inteiro: reduzir riscos de
manutenção por refatorações localizadas, preservando o comportamento funcional do
cadastro de colaboradores e preparando a estrutura para novos requisitos.

Fundamentação: catálogo de refatorações de **Fowler** (Extract Class, Extract
Method, Introduce Parameter Object, Replace Conditional with Polymorphism,
Introduce Interface) e padrões **GoF** (Strategy, Factory Method, Builder,
Adapter, Facade, Observer) + padrões arquiteturais (Repository, Specification,
Query Object).

**Premissas de execução:**
- Manter compatibilidade com os fluxos existentes.
- Não alterar o banco de dados na primeira etapa.
- Preservar endpoints e contratos usados pela interface.
- Criar testes de caracterização antes das mudanças sensíveis.
- Registrar cada intervenção em branch e pull request.
- Medir redução de complexidade por inspeção, testes e análise estática.

## 3.2 Novos requisitos considerados

| Código | Novo requisito | Descrição | Justificativa |
|---|---|---|---|
| NR01 | Validação centralizada de cadastro | Validar dados obrigatórios, formato de documento, e-mail, matrícula e políticas de imagem em um ponto único. | Reduzir duplicidade de regras entre frontend, controller e service. |
| NR02 | Busca avançada de colaboradores | Filtros por unidade, status, cargo, data de admissão, supervisor e identificadores funcionais. | Evitar crescimento desorganizado do `EmployeeDao`. |
| NR03 | Auditoria de alterações sensíveis | Registrar criação/alteração/exclusão com usuário, data, ação e campos. | Garantir rastreabilidade no domínio de RH. |
| NR04 | Políticas variáveis por organização | Regras diferentes para documento, foto, campos obrigatórios e identificador funcional. | Suportar variações sem inserir condicionais em várias camadas. |
| NR05 | Integração futura com APIs externas | Contrato de aplicação mais estável para cadastro e consulta. | Separar contrato de domínio da estrutura específica do framework. |

## 3.3 Inventário de code smells

| Code smell | Evidência no PIM | Impacto | Refatoração proposta | Padrão / requisito |
|---|---|---|---|---|
| Large Class / God Class | `Employee` concentra muitos atributos e relações. | Alto: mudanças no domínio afetam vários fluxos. | Extract Class; separar objetos de valor. | Value Object, Builder; NR01/NR04 |
| Long Method / Complex Query | `EmployeeDao` concentra filtros, joins, paginação. | Alto: novos filtros deixam métodos maiores e menos testáveis. | Extract Method; Replace Method with Query Object. | Specification, Query Object; NR02 |
| Primitive Obsession | IDs, flags, strings de status circulam como tipos primitivos. | Médio/alto: semântica fraca e validações repetidas. | Introduce Parameter Object; `EmployeeSearchCriteria`, `EmployeeRegistrationRequest`. | DTO, Parameter Object, Value Object; NR01/NR02 |
| Shotgun Surgery | Regras de cadastro/validação distribuídas em várias camadas. | Alto: pequena mudança exige ajustes em vários arquivos. | Move Method; Extract Service; centralizar validações. | Strategy; NR01/NR04 |
| Feature Envy / Acoplamento Service-DAO | `EmployeeService` depende de detalhes de persistência. | Médio/alto: dificulta testes e substituição da persistência. | Introduce Interface; Dependency Inversion; ocultar DAO atrás de contrato. | Repository, Adapter; NR05 |
| Duplicated Validation Logic | Validações repetidas entre frontend e backend. | Médio: divergência pode causar erros inconsistentes. | Extract Class; pipeline de validação reutilizável. | Strategy, Chain of Responsibility; NR01/NR04 |
| Controller Coupling | Controllers dependem de classe base, props Vue e serviços internos. | Médio: testes isolados exigem contexto da aplicação. | Thin Controller; extrair casos de uso. | Facade, Application Service; NR05 |
| Hidden Side Effects | Cadastro aciona eventos/normalizações pouco explícitos. | Médio: efeitos colaterais dificultam entendimento e testes. | Encapsulate Side Effects; publicar eventos de domínio. | Observer/Event Dispatcher; NR03 |
| Data Clumps | Conjuntos de campos trafegam juntos em vários métodos. | Médio: assinaturas crescem e mudam com frequência. | Introduce Parameter Object; agrupar dados correlatos. | DTO, Builder; NR01/NR02 |
| Low Testability | Camadas dependem de framework, banco e serviços globais. | Alto: aumenta custo de regressão e dificulta evolução segura. | Dependency Injection; criar contratos e mocks; testes de caracterização. | Repository, Adapter, Facade; todos os NR |

## 3.4 Priorização do plano de refatoração

P1 = necessário antes de funcionalidade nova; P2 = importante, pode ser
paralelo; P3 = organização/manutenção de menor urgência.

| Prioridade | Ação | Motivo | Critério de conclusão |
|---|---|---|---|
| P1 | Criar testes de caracterização do cadastro e busca. | Proteger o comportamento atual. | Fluxos principais cobertos por testes. |
| P1 | Introduzir `EmployeeRegistrationRequest` e `EmployeeSearchCriteria`. | Reduz Primitive Obsession e Data Clumps. | Assinaturas estáveis e validações centralizadas. |
| P1 | Extrair validadores de cadastro com Strategy. | Permite NR01 e NR04 sem duplicar condicionais. | Validações independentes e testáveis. |
| P1 | Criar `EmployeeRepository` (contrato) e `EmployeeDaoAdapter`. | Reduz acoplamento direto serviço↔DAO. | Service testável com mock de repository. |
| P2 | Separar Query Objects e Specifications para busca avançada. | Evita crescimento do `EmployeeDao`. | Filtros testáveis individualmente. |
| P2 | Adicionar Observer/Event Dispatcher para auditoria. | Atende NR03 sem espalhar logs. | Eventos explícitos de criação/alteração/exclusão. |
| P2 | Criar Builder/Factory para construção de `Employee`. | Reduz duplicidade na criação de entidade. | Construção consistente a partir de DTO. |
| P3 | Reorganizar nomes, comentários técnicos e contratos. | Melhora compreensão da arquitetura alvo. | Documentação compatível com manutenção futura. |

## 3.5 Arquitetura alvo proposta

Mantém a separação em camadas, mas introduz contratos e objetos especializados.
O controller torna-se **fino** (recebe requisição, aciona caso de uso, formata
resposta). As regras de cadastro passam a um **serviço de aplicação**,
validadores especializados (Strategy) e objetos de requisição (Parameter Object).
A persistência continua usando o DAO atual, **isolada por uma interface de
repositório e um adapter**.

Ver `docs/uml/pim-target-architecture.puml` (Figura 3).

- **Facade** — entrada de alto nível para operações de colaborador.
- **Strategy** — troca de regras de validação.
- **Builder** — organiza a criação da entidade `Employee`.
- **Adapter** — aproveita o DAO atual sem expor seus detalhes.
- **Repository** — define contrato de persistência.
- **Specification / Query Object** — isola filtros de busca.
- **Observer** — viabiliza auditoria sem espalhar chamadas de log.

## 3.6 Refatorações e padrões aplicados

| Refatoração | Aplicação no PIM | Padrão relacionado | Benefício |
|---|---|---|---|
| Extract Class | Separar validadores, critérios e objetos de valor. | Strategy, Specification, Value Object | Reduz Large Class, Shotgun Surgery, Primitive Obsession. |
| Introduce Parameter Object | `EmployeeRegistrationRequest`, `EmployeeSearchCriteria`. | DTO / Parameter Object | Centraliza entrada e simplifica assinaturas. |
| Introduce Interface | `EmployeeRepository` para desacoplar do `EmployeeDao`. | Repository + Adapter | Mocks em testes e substituição controlada. |
| Replace Conditional with Polymorphism | Política de validação por validadores intercambiáveis. | Strategy | Atende políticas variáveis sem condicionais. |
| Extract Method | Quebrar métodos longos de consulta/persistência. | Query Object | Reduz complexidade do DAO. |
| Move Method | Mover regras de validação para a camada adequada. | Application Service + Strategy | Comportamento na camada correta. |
| Encapsulate Side Effects | Publicar eventos após criar/alterar/excluir. | Observer | Auditoria desacoplada. |
| Replace Constructor with Builder | Criar `Employee` a partir de request validado. | Builder / Factory Method | Evita criação incompleta/inconsistente. |

## 3.7 Roadmap de execução

Branch: `feature/refactor-pim-services`. Commits pequenos e rastreáveis, com PR
para `development` após validação.

| Etapa | Nome | Descrição | Entregável |
|---|---|---|---|
| 0 | Baseline de segurança | Registrar estado atual, executar testes, coletar métricas. | Relatório de baseline e evidências. |
| 1 | Normalização de entrada | `EmployeeRegistrationRequest` e `EmployeeSearchCriteria` sem alterar comportamento externo. | Fluxo atual com objetos de entrada. |
| 2 | Validação por Strategy | Extrair validadores (documento, obrigatórios, e-mail, imagem). | Validações testáveis isoladamente. |
| 3 | Repository Adapter | `EmployeeRepository` + adapter para `EmployeeDao`. | Service usa contrato, não implementação. |
| 4 | Busca avançada | Query Object/Specification para filtros do `EmployeeDao`. | Filtros sem inflar métodos do DAO. |
| 5 | Auditoria por eventos | `EmployeeChangedEvent` + observer. | Registro de alterações desacoplado. |
| 6 | Validação regressiva e CI | Testes, análise estática, preparar DevOps. | Relatório de regressão (checkpoint 04/05). |

## 3.8 Critérios de aceite, riscos e rastreabilidade

| Critério | Condição de aceite | Forma de verificação |
|---|---|---|
| Compatibilidade funcional | Cadastro/consulta/edição/exclusão funcionam como antes. | Testes de caracterização e integração. |
| Redução de acoplamento | `EmployeeService` deixa de depender diretamente de `EmployeeDao`. | Inspeção de dependências e uso de interface. |
| Validações centralizadas | Regras executadas por validadores específicos. | Testes unitários por validador. |
| Busca extensível | Novos filtros em objetos de critério/especificação. | Teste de cada filtro e revisão do DAO. |
| Auditoria desacoplada | Eventos acionam observer sem chamada direta. | Teste de evento e registro. |
| Rastreabilidade | Cada alteração possui branch, commit, descrição e evidência. | Histórico Git e relatório do checkpoint. |

**Riscos principais:** regressão em fluxos sensíveis de RH, alteração acidental
do contrato Vue, divergência entre validações antigas e novas, excesso de
abstração e dificuldade de testar por dependência de banco/framework.
**Mitigação:** começar por testes de caracterização, manter adapters compatíveis
com o DAO atual, limitar mudanças de banco, revisar cada etapa por PR e manter
rollback por commits pequenos.

### Matriz de rastreabilidade (defesa técnica)

| Problema priorizado | Requisito | Solução proposta | Resultado esperado |
|---|---|---|---|
| Large Class / `Employee` | NR01, NR04 | Extract Class, Builder, Value Object | Separar responsabilidades e reduzir impacto de mudanças. |
| `EmployeeDao` complexo | NR02 | Query Object, Specification | Adicionar filtros sem aumentar métodos longos. |
| Regras distribuídas | NR01, NR04 | Strategy, Application Service | Centralizar validações e facilitar testes. |
| Acoplamento com DAO | NR05 | Repository, Adapter | Isolar persistência e facilitar mock em testes. |
| Ausência de auditoria clara | NR03 | Observer/Event Dispatcher | Registrar eventos sem espalhar chamadas diretas. |
| Baixa testabilidade | Todos | Facade, Interface, Dependency Injection | Testes unitários e regressivos antes da automação DevOps. |
