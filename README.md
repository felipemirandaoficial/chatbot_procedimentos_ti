📚 Sistema de Documentação e Procedimentos de TI (SIA)

Sistema web desenvolvido em PHP + MySQL/MariaDB para gerenciamento centralizado de procedimentos técnicos de TI, com suporte a:

✔ Cadastro estruturado de procedimentos

✔ Upload de PDF com extração automática de texto

✔ Organização por categorias

✔ Busca rápida via DataTables

✔ Chatbot com IA para auxílio na execução dos procedimentos

✔ Interface moderna com Bootstrap

🚀 Visão Geral

Este sistema foi criado para servir como uma base de conhecimento interna de TI, permitindo documentar rotinas operacionais, troubleshooting, padrões e manuais técnicos de forma organizada e acessível.

Ideal para:

Equipes de suporte e infraestrutura

NOC / Service Desk

Documentação de processos internos

Onboarding técnico

Centralização de conhecimento organizacional

🧠 Recursos de IA (SIA Chatbot)

O sistema possui um chatbot integrado capaz de:

Localizar procedimentos relevantes

Explicar etapas

Auxiliar na execução

Fornecer contexto automático do procedimento aberto

Operar em modo global (busca geral)

🧾 Funcionalidades
📌 Gestão de Procedimentos

Cadastro completo com:

Título

Categoria

Sistema/Local

Objetivo

Quando utilizar

Conteúdo detalhado

Edição e visualização

Organização por categorias

Listagem dinâmica via DataTables

📄 Upload de PDF com OCR textual

É possível enviar um manual em PDF. O sistema:

Salva o arquivo

Executa script Python

Extrai o texto automaticamente

Preenche o conteúdo do procedimento

Gera resumo para uso pela IA

🤖 Chat Inteligente

Chat contextual por procedimento

Chat global para consultas gerais

Histórico salvo no navegador (localStorage)

Limite de 500 caracteres por mensagem

🛠️ Tecnologias Utilizadas
Backend

PHP 8+

MySQL / MariaDB

PDO

Python (opcional para extração de PDF)

Frontend

Bootstrap 5

jQuery

DataTables

JavaScript puro

IA

OpenAI API (ChatGPT)



⚙️ Instalação
1️⃣ Clonar o repositório
git clone 'this'

2️⃣ Banco de Dados

Crie o banco:

CREATE DATABASE tecnologia;

Importe os arquivos .sql da pasta:

/banco

3️⃣ Configuração do Banco

Edite o arquivo:

db.php

Com suas credenciais:

$pdo = new PDO(
    "mysql:host=localhost;dbname=tecnologia;charset=utf8",
    "usuario",
    "senha"
);

4️⃣ (Opcional) Extração de PDF para Texto

Para habilitar a conversão automática:

Instale o Python

Recomendado usar ambiente virtual:

python -m venv venv

Ative:

Windows:

venv\Scripts\activate

Linux:

source venv/bin/activate

Instale a biblioteca:

pip install pdfplumber

O sistema executa:

upload/extrair_pdf.py


5️⃣ Configurar API OpenAI

Edite:
openai.php

Insira sua chave:
$apiKey = "SUA_API_KEY";

6️⃣ Permissões de Pasta
Garanta permissão de escrita:
/upload

🔐 Login (Opcional)

O sistema permite restrição por sessão.
Caso deseje exigir autenticação, configure no db.php:

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit();
}


💡 Casos de Uso

Documentação de troubleshooting

Procedimentos de incidentes

Padrões operacionais

Playbooks de infraestrutura

Base de conhecimento corporativa

📸 Interface

Dashboard simples e direto

Modal para cadastro

Visualização rápida do conteúdo

Chat flutuante com IA
