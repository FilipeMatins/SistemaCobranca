# 📝 Bloco de Cobranças

Sistema simples para gerenciar cobranças de clientes via WhatsApp.

## 🚀 Instalação

### 1. Criar o Banco de Dados

```sql
-- No phpMyAdmin, importe o arquivo database.sql
```

### 2. Configurar Conexão

Edite `config/database.php` se necessário:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_cobranca');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Acessar

```
http://localhost/SistemaCobranca/
```

## 📁 Estrutura do Projeto

```
SistemaCobranca/
├── api/                    # APIs REST
│   ├── clientes.php        # CRUD clientes (autocomplete)
│   ├── configuracoes.php   # Configurações do sistema
│   ├── empresas.php        # CRUD empresas (autocomplete)
│   ├── enviar_cobrancas.php # Envio automático (futuro)
│   └── notinhas.php        # CRUD notinhas
│
├── assets/                 # Arquivos estáticos
│   ├── css/
│   │   └── app.css         # Estilos da aplicação
│   └── js/
│       └── app.js          # JavaScript da aplicação
│
├── config/
│   └── database.php        # Configuração do banco
│
├── logs/                   # Logs do sistema
│
├── index.php               # Página principal
├── database.sql            # Script do banco de dados
└── README.md
```

## ⚙️ Configurações

Clique em **⚙️ Configurações** para definir:

- **Chave PIX** - Sua chave para recebimento
- **Nome do Vendedor** - Como você aparece na mensagem
- **Mensagem de Cobrança** - Personalize a mensagem enviada

### Variáveis da Mensagem

| Variável | Descrição |
|----------|-----------|
| `{nome}` | Primeiro nome do cliente |
| `{vendedor}` | Seu nome |
| `{valor}` | Valor da cobrança |
| `{pix}` | Sua chave PIX |

**Exemplo de mensagem:**
```
Bom dia {nome} tudo bem? {vendedor}, passando para deixar meu pix e o valor dos produtos 🙏 {valor} Chave pix {pix}
```

## ✨ Funcionalidades

- ✅ Cadastro automático de empresas e clientes
- ✅ Autocomplete ao digitar
- ✅ Múltiplos clientes por notinha
- ✅ Alertas de vencimento (hoje/atrasadas)
- ✅ Busca e filtros
- ✅ Edição de notinhas
- ✅ Mensagem personalizável
- ✅ Envio via WhatsApp
- ✅ Interface responsiva

## 📱 Como Usar

1. **Nova Notinha** - Informe empresa, data e clientes
2. **Salvar** - A notinha fica salva no sistema
3. **Cobrar** - Clique no botão 💬 para enviar via WhatsApp
4. **Editar** - Clique em ✏️ para alterar
5. **Excluir** - Clique em 🗑️ para remover
