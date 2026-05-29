# 🪙 MyPocket — Gerenciador Financeiro Pessoal

O **MyPocket** é um ecossistema de organização financeira pessoal robusto, focado no controle de endividamento e saúde financeira doméstica. O projeto foi desenvolvido como critério de avaliação para a disciplina de **Programação Web 2 (PW2)**.

O grande diferencial deste sistema é o seu motor de regras de negócio baseado puramente no paradigma de **Programação Orientada a Objetos (POO)** com **Tipagem Estrita (PHP 8.1+)**, garantindo que nenhuma transação seja burlada ou inserida sem as devidas validações de segurança.

---

## 🚀 Funcionalidades Principais

* **Controle de Endividamento Estrito:** O sistema atua de forma preventiva. A carteira barra automaticamente qualquer tentativa de gasto que ultrapasse o saldo líquido disponível.
* **Classificação Automática de Fluxo:** Separação polimórfica entre Receitas (Entradas) e Despesas (Saídas).
* **Histórico Inalterável (Auditoria):** Transações não podem ser editadas ou apagadas, simulando um livro-caixa real. Correções devem ser feitas via estorno.
* **Extrato Consolidado Dinâmico:** Listagem cronológica com diferenciação visual baseada no tipo de movimentação.
* **Alternador de Tema Nativo (Light/Dark Mode):** Interface adaptável que salva a preferência do usuário no navegador (`localStorage`).
* **Exportação para Excel/CSV:** Funcionalidade analítica para baixar todo o histórico em formato `.csv` tratado para caracteres da língua portuguesa (UTF-8 com BOM).

---

## 🛠️ Pilares de POO Aplicados (Requisitos Técnicos)

* **Abstração & Herança:** Criação da classe abstrata `Transacao`, que serve de molde obrigatório e compartilha atributos e métodos para as classes filhas `Receita` e `Despesa`.
* **Encapsulamento Rigoroso:** Atributos críticos como `$saldo` e `$historico` utilizam visibilidade `private`. O saldo jamais é alterado diretamente de fora da classe, sendo modificado apenas por métodos validadores internos (`addTransacao`).
* **Polimorfismo:** O método abstrato `getTipo()` é sobrescrito pelas classes filhas, permitindo que o sistema descubra dinamicamente se trata-se de uma Entrada ou Saída em tempo de execução.
* **Métodos Mágicos:** Implementação do método construtor `__construct` nas entidades de dados.
* **Tratamento de Exceções:** Lançamento de `Exception` caso o limite de saldo seja violado, capturado por blocos `try/catch` na camada de controle para exibição de alertas amigáveis (UX).
* **Padrão PRG (Post-Redirect-Get):** Proteção do fluxo HTTP contra reenvio de dados e duplicidade de lançamentos ao atualizar a página (F5).

---

## 📂 Estrutura de Arquivos

/mypocket
  ├── classes/
  │    ├── Transacao.php  (Classe Abstrata Mãe)
  │    ├── Receita.php    (Classe Filha - Herda de Transacao)
  │    ├── Despesa.php    (Classe Filha - Herda de Transacao)
  │    └── Carteira.php   (Gerencia Saldo, Coleções e Regras de Negócio)
  ├── processa.php        (Controlador - Captura inputs, manipula objetos e gerencia a sessão)
  ├── exportar.php        (Serviço - Converte a coleção de objetos da sessão em arquivo CSV)
  └── index.php           (Visão - Interface responsiva integrada ao Bootstrap 5.3)

⚙️ Tecnologias Utilizadas
PHP 8.1+ (Com declare(strict_types=1); em todos os arquivos de lógica)

Bootstrap 5.3 (Sistema de Grid responsivo e suporte a Light/Dark Mode)

JavaScript (Vanilla) (Manipulação do DOM para troca de temas e persistência em localStorage)

Sessões PHP (Persistência do estado do objeto Carteira entre requisições)

💻 Como Rodar o Projeto
Certifique-se de ter um servidor local com suporte a PHP 8.1 ou superior instalado (como o XAMPP, WampServer ou Laragon).

Clone ou mova a pasta do projeto para o diretório raiz do seu servidor local (ex: C:\xampp\htdocs\ ou D:\...\www\).

Abra o navegador e acesse:

Plaintext
http://localhost/mypocket/index.php
Comece a lançar suas receitas e despesas!
