# 🪙 MyPocket — Gerenciador Financeiro Pessoal

O **MyPocket** é um ecossistema de organização financeira pessoal robusto, focado no controle de endividamento e saúde financeira doméstica. O projeto foi desenvolvido como critério de avaliação para a disciplina de **Programação Web 2 (PW2)**.

O grande diferencial deste sistema é o seu motor de regras de negócio baseado puramente no paradigma de **Programação Orientada a Objetos (POO)** com **Tipagem Estrita (PHP 8.1+)**, garantindo que nenhuma transação seja burlada ou inserida sem as devidas validações de segurança.

---

## 🚀 Funcionalidades Principais

* ✅ **Controle de Endividamento Estrito**
  O sistema atua de forma preventiva. A carteira barra automaticamente qualquer tentativa de gasto que ultrapasse o saldo líquido disponível.

* 💸 **Classificação Automática de Fluxo**
  Separação polimórfica entre Receitas (Entradas) e Despesas (Saídas).

* 📜 **Histórico Inalterável (Auditoria)**
  Transações não podem ser editadas ou apagadas, simulando um livro-caixa real. Correções devem ser feitas via estorno.

* 📊 **Extrato Consolidado Dinâmico**
  Listagem cronológica com diferenciação visual baseada no tipo de movimentação.

* 🌙 **Alternador de Tema Nativo (Light/Dark Mode)**
  Interface adaptável que salva a preferência do usuário no navegador (`localStorage`).

* 📁 **Exportação para Excel/CSV**
  Funcionalidade analítica para baixar todo o histórico em formato `.csv` tratado para caracteres da língua portuguesa (UTF-8 com BOM).

---

## 🛠️ Pilares de POO Aplicados (Requisitos Técnicos)

### 🔹 Abstração & Herança

Criação da classe abstrata `Transacao`, que serve de molde obrigatório e compartilha atributos e métodos para as classes filhas `Receita` e `Despesa`.

### 🔹 Encapsulamento Rigoroso

Atributos críticos como `$saldo` e `$historico` utilizam visibilidade `private`. O saldo jamais é alterado diretamente de fora da classe, sendo modificado apenas por métodos validadores internos (`addTransacao`).

### 🔹 Polimorfismo

O método abstrato `getTipo()` é sobrescrito pelas classes filhas, permitindo que o sistema descubra dinamicamente se trata-se de uma Entrada ou Saída em tempo de execução.

### 🔹 Métodos Mágicos

Implementação do método construtor `__construct` nas entidades de dados.

### 🔹 Tratamento de Exceções

Lançamento de `Exception` caso o limite de saldo seja violado, capturado por blocos `try/catch` na camada de controle para exibição de alertas amigáveis (UX).

### 🔹 Padrão PRG (Post-Redirect-Get)

Proteção do fluxo HTTP contra reenvio de dados e duplicidade de lançamentos ao atualizar a página (`F5`).

---

## 📂 Estrutura de Arquivos

```text
/mypocket
├── classes/
│   ├── Transacao.php   # Classe Abstrata Mãe
│   ├── Receita.php     # Classe Filha - Herda de Transacao
│   ├── Despesa.php     # Classe Filha - Herda de Transacao
│   └── Carteira.php    # Gerencia saldo, regras e histórico
│
├── processa.php        # Controlador das transações
├── exportar.php        # Geração de arquivo CSV
└── index.php           # Interface principal integrada ao Bootstrap
```

---

## ⚙️ Tecnologias Utilizadas

* **PHP 8.1+**

  * Uso de `declare(strict_types=1);`
  * Programação Orientada a Objetos
  * Tratamento de exceções

* **Bootstrap 5.3**

  * Grid responsivo
  * Componentes modernos
  * Suporte nativo a Light/Dark Mode

* **JavaScript (Vanilla)**

  * Manipulação do DOM
  * Persistência do tema via `localStorage`

* **Sessões PHP**

  * Persistência do estado do objeto `Carteira`

---

## 💻 Como Rodar o Projeto

### 1️⃣ Instale um servidor local

Utilize algum ambiente com suporte a **PHP 8.1+**, como:

* XAMPP
* WampServer
* Laragon

### 2️⃣ Clone o repositório

```bash
git clone https://github.com/seuusuario/mypocket.git
```

### 3️⃣ Mova o projeto para o diretório do servidor

Exemplo:

```text
C:\xampp\htdocs\
```

ou

```text
D:\laragon\www\
```

### 4️⃣ Inicie o servidor Apache

### 5️⃣ Acesse no navegador

```text
http://localhost/mypocket/index.php
```

---

## 📸 Preview

> Interface responsiva com suporte a modo claro/escuro e exportação de dados financeiros.

---

## 📚 Objetivo Acadêmico

Projeto desenvolvido para aplicação prática dos conceitos de:

* Programação Orientada a Objetos (POO)
* PHP Moderno
* Encapsulamento
* Herança
* Polimorfismo
* Sessões
* Manipulação de arquivos CSV
* Integração frontend/backend

---

## 👩‍💻 Desenvolvido por

**Samira Carvalho Toledo**
Projeto acadêmico — ETEC Zona Leste
