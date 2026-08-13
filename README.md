# 🪙 MyPocket — Gerenciador Financeiro Pessoal

O **MyPocket** é um sistema de organização financeira pessoal desenvolvido para auxiliar no controle de receitas, despesas e saldo disponível.

O projeto foi desenvolvido como critério de avaliação para a disciplina de **Programação Web 2 (PW2)**, utilizando PHP, MySQL, Programação Orientada a Objetos (POO), Bootstrap e JavaScript.

O sistema possui autenticação de usuários, armazenamento das transações em banco de dados, controle de saldo e validações para impedir gastos superiores ao saldo disponível.

---

## 🚀 Funcionalidades Principais

- 🔐 **Cadastro e Login de Usuários**
  Permite criar uma conta e realizar login utilizando e-mail e senha protegida por hash.

- 👤 **Sistema de Sessões**
  Cada usuário possui sua própria sessão e consegue visualizar somente suas próprias transações.

- 💸 **Controle de Saldo**
  O sistema calcula o saldo a partir das receitas e despesas cadastradas.

- 🚫 **Controle de Endividamento**
  O sistema impede automaticamente que uma despesa seja registrada quando seu valor for maior que o saldo disponível.

- 💰 **Classificação de Transações**
  Separação entre:
  - Entrada — Receita/Ganho
  - Saída — Despesa/Gasto

- 📜 **Histórico de Transações**
  Exibição das movimentações cadastradas pelo usuário em formato de extrato.

- ✏️ **Edição de Transações**
  Permite alterar informações de uma transação já cadastrada, respeitando as regras de saldo.

- 🗑️ **Exclusão de Transações**
  Permite remover uma transação do histórico, com confirmação antes da exclusão.

- 📊 **Extrato Consolidado**
  Exibição cronológica das transações com diferenciação visual entre entradas e saídas.

- 📁 **Exportação para CSV**
  Permite exportar as transações do usuário para um arquivo `.csv`, compatível com Excel e outros editores de planilhas.

- 🔔 **Alertas com SweetAlert2**
  Utilização de alertas visuais para informar erros, confirmações e ações realizadas no sistema.

- 📱 **Interface Responsiva**
  Interface desenvolvida com Bootstrap, adaptando-se a diferentes tamanhos de tela.

---

## 🗄️ Banco de Dados

O projeto utiliza **MySQL** para armazenar os usuários e suas transações.

O arquivo:

```text
sistema_crud.sql
