# Sprint 3.5 — Front-base e acesso pelo navegador

## Objetivo

Colocar o Health Prospect CRM em um estado visual navegável antes da Sprint 4, mantendo os módulos Empresas e Contatos funcionais e apresentando os próximos módulos como áreas planejadas.

## Escopo

- shell principal responsivo;
- sidebar comercial completa;
- header com usuário/role;
- dashboard comercial inicial;
- indicadores de Companies e Contacts respeitando RBAC;
- atalhos rápidos;
- placeholders navegáveis de Leads, Pipeline, Atividades, Tarefas, Campanhas e Relatórios;
- navegação mobile;
- testes do dashboard e proteção contra vazamento de métricas;
- validação em Debian + Apache + PHP-FPM.

## Validação técnica antes de publicar

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=1G
pnpm install --frozen-lockfile
pnpm run build
git diff --check
```

## Acesso rápido para desenvolvimento

Se o código já estiver no servidor Debian e você quiser validar antes do Apache:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Depois acesse de outra máquina da mesma rede:

```text
http://IP_DO_SERVIDOR:8000
```

Este modo é apenas para validação/desenvolvimento. O acesso definitivo deve usar Apache apontando o DocumentRoot para `public/`.

## Apache — VirtualHost HTTP para rede local

Exemplo assumindo o projeto em `/var/www/health-prospect-crm`:

```apache
<VirtualHost *:80>
    ServerName crm.local
    DocumentRoot /var/www/health-prospect-crm/public

    <Directory /var/www/health-prospect-crm/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/health-prospect-crm-error.log
    CustomLog ${APACHE_LOG_DIR}/health-prospect-crm-access.log combined
</VirtualHost>
```

Ativação:

```bash
sudo a2enmod rewrite
sudo a2ensite health-prospect-crm.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Para produção, usar HTTPS e PHP-FPM conforme a arquitetura do projeto.

## Permissões recomendadas

```bash
sudo chown -R $USER:www-data /var/www/health-prospect-crm
sudo find /var/www/health-prospect-crm -type d -exec chmod 755 {} \;
sudo find /var/www/health-prospect-crm -type f -exec chmod 644 {} \;
sudo chmod -R ug+rwx /var/www/health-prospect-crm/storage /var/www/health-prospect-crm/bootstrap/cache
```

Não versionar `.env` e não expor PostgreSQL/Redis diretamente na Internet.
