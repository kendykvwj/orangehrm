# Checkpoint 02 — Engenharia Reversa

**Módulo analisado:** PIM (`orangehrmPimPlugin`) — fluxo de cadastro de colaborador.

## 2.1 Objetivo

Recuperar o entendimento arquitetural do módulo PIM a partir dos artefatos
existentes, antes de propor reengenharia. Identificar quais classes participam
do fluxo de cadastro, as dependências entre controller, serviço, DAO, entidade e
banco, e os pontos de maior risco de manutenção.

## 2.2 Metodologia de análise (três etapas)

| Etapa | Atividade | Resultado esperado |
|---|---|---|
| 1 | Leitura da árvore de diretórios e identificação dos módulos. | Mapa inicial do repositório e seleção do módulo PIM. |
| 2 | Análise de classes do fluxo de colaborador. | Lista de controllers, services, DAOs e entidades relevantes. |
| 3 | Modelagem UML e relatório textual. | Diagramas de classe e sequência + análise da arquitetura recuperada. |

Arquivos representativos analisados: `SaveEmployeeController.php`,
`EmployeeService.php`, `EmployeeDao.php`, `Employee.php`.

## 2.3 Arquitetura recuperada do módulo PIM

Aplicação modular em PHP, separada por plugins. No PIM a estrutura principal
divide-se em controllers, services, DAOs, entidades, DTOs e configuração.

| Camada / pacote | Responsabilidade recuperada | Exemplos observados |
|---|---|---|
| Controller | Preparar telas, componentes e dados para interação do usuário. | `SaveEmployeeController`, `EmployeeController`, `EmployeePersonalDetailController` |
| Service | Orquestrar operações de negócio e eventos do colaborador. | `EmployeeService`, `EmployeeSalaryService`, `EmploymentContractService` |
| Dao | Executar consultas, filtros, paginação e persistência. | `EmployeeDao`, `EmployeeAttachmentDao`, `EmployeeDependentDao` |
| Entity | Representar objetos persistentes do domínio de RH. | `Employee`, `EmployeeSalary`, `EmpContract`, `EmpDependent` |
| Dto / Model | Transportar parâmetros de busca e dados normalizados. | `EmployeeSearchFilterParams`, `EmployeeModel` |
| Framework / ORM | Controller base, query builder, normalização e serviços auxiliares. | `AbstractVueController`, `QueryBuilderWrapper`, `BaseDao` |

Ver `docs/uml/pim-class-diagram.puml` (Figura 1) e
`docs/uml/pim-sequence-add-employee.puml` (Figura 2).

## 2.4 / 2.5 Diagramas

- **Classes (estado atual):** `SaveEmployeeController` herda de
  `AbstractVueController`, usa serviços e entidades para preparar a interface.
  `EmployeeService` centraliza operações e depende de `EmployeeDao`.
  `EmployeeDao` depende de ORM/query builder. `Employee` é o elemento central.
- **Sequência (cadastro):** dois momentos — preparação da interface pelo
  controller e envio dos dados, que passam pela camada de aplicação até a
  persistência.

## 2.6 Débitos técnicos identificados (achados iniciais)

| Débito técnico | Evidência no recorte PIM | Risco para manutenção |
|---|---|---|
| Classe extensa / concentração de domínio | `Employee` possui grande quantidade de atributos e relações. | Mudanças em dados do colaborador afetam muitos pontos. |
| DAO com consultas complexas | `EmployeeDao` concentra filtros, joins, paginação e critérios. | Novos filtros aumentam complexidade e geram regressões. |
| Acoplamento com framework | Controllers dependem de classes base, componentes Vue, props e serviços internos. | Dificulta testes unitários isolados; exige contexto da aplicação. |
| Dependência direta de serviços auxiliares | `EmployeeService` acessa DAOs, eventos, normalizadores, usuários e configuração. | Aumenta acoplamento e torna testes mais trabalhosos. |
| Possível Primitive Obsession em parâmetros | Filtros, IDs, campos de busca e flags circulam por parâmetros e DTOs. | Dificulta validações centralizadas e clareza semântica. |
| Regras distribuídas | Validações e decisões espalhadas entre controller, service, DAO, entidade e frontend. | Regra de negócio difícil de localizar. |

## 2.7 Relatório de análise da arquitetura recuperada

O OrangeHRM **não** é uma aplicação sem organização: há separação explícita em
módulos e camadas. Porém o tamanho das classes de domínio e persistência, a
quantidade de dependências e a distribuição de responsabilidades indicam
oportunidades reais de melhoria.

- **Ponto central:** entidade `Employee`, que agrega grande parte das
  informações e se conecta a salário, contrato, dependentes, contatos e
  documentos. Qualquer alteração nesse domínio exige testes, análise de impacto
  e controle de versão.
- **Ponto crítico de manutenção:** camada DAO, que concentra construção de
  consultas e filtros. Recomenda-se investigar reorganização em objetos de
  especificação, query objects ou métodos menores.
- **Melhoria futura:** ampliar injeção de dependência explícita, interfaces e
  contratos de repositório para facilitar mocks, testes e substituição
  controlada de implementações.

**Conclusão:** o módulo PIM possui arquitetura recuperável, domínio adequado e
débitos técnicos compatíveis com uma proposta de revitalização. O checkpoint 03
transforma esse diagnóstico em inventário de code smells e plano de reengenharia
fundamentado em Fowler e nos padrões GoF.
