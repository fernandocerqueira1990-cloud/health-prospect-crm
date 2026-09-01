# Acesso externo seguro em ambiente de testes

## Contexto

Após a evolução das funcionalidades principais do CRM, o projeto passou a validar o acesso fora da rede local. Esta etapa tem caráter **experimental e controlado**: o objetivo é testar o comportamento da aplicação em acesso externo antes de qualquer publicação definitiva em produção.

A decisão foi evitar exposição direta do servidor de aplicação à internet e utilizar **Cloudflare Tunnel** como camada intermediária de acesso.

> Esta documentação descreve o ambiente de testes atual. Não representa ainda a arquitetura definitiva de produção.

## Objetivo da etapa

Validar o acesso externo ao CRM X com uma arquitetura que reduza a superfície de exposição do servidor e permita testar domínio, HTTPS, proxies, sessão, aplicação e banco de dados em condições mais próximas de um ambiente real.

Os principais objetivos desta fase são:

- permitir acesso externo controlado ao CRM;
- evitar exposição direta do servidor Debian à internet;
- utilizar HTTPS no acesso ao ambiente de testes;
- validar o comportamento do Laravel atrás de proxy/túnel;
- manter PostgreSQL e Redis restritos ao ambiente interno;
- observar erros, logs e comportamento da aplicação antes de produção;
- identificar ajustes de infraestrutura e segurança necessários para o deploy definitivo.

## Arquitetura utilizada nos testes

```text
Usuário / Internet
        |
        v
Cloudflare
DNS + HTTPS + proteção de borda
        |
        v
Cloudflare Tunnel
        |
        v
Servidor Debian — ambiente de testes
        |
        v
Apache + PHP + Laravel
        |
        +-------------------+
        |                   |
        v                   v
   PostgreSQL             Redis
   Banco de dados         Cache / filas
```

### Fluxo de acesso

1. O usuário acessa o endereço configurado no domínio/subdomínio.
2. O DNS é tratado pela Cloudflare.
3. A conexão externa utiliza HTTPS.
4. O Cloudflare Tunnel encaminha a requisição para o serviço configurado no servidor de testes.
5. O Apache recebe a requisição e entrega a aplicação Laravel.
6. A aplicação acessa PostgreSQL e Redis internamente, sem necessidade de exposição pública desses serviços.

## Por que utilizar Cloudflare Tunnel nesta fase

Em um cenário tradicional, publicar uma aplicação pode exigir abertura de portas no roteador/firewall e exposição direta do endereço do servidor.

Para esta fase de testes, o túnel permite estabelecer uma conexão de saída a partir do ambiente interno para a infraestrutura da Cloudflare, reduzindo a necessidade de expor diretamente o servidor de origem.

Isso não elimina a necessidade de hardening, controle de acesso, atualização do sistema operacional, proteção da aplicação, logs e monitoramento. O túnel é apenas uma das camadas da arquitetura.

## Componentes envolvidos

| Camada | Tecnologia | Função nesta etapa |
|---|---|---|
| DNS / borda | Cloudflare | Resolução de nome e camada externa de acesso |
| Transporte | HTTPS | Criptografia da comunicação externa |
| Túnel | Cloudflare Tunnel | Encaminhamento do tráfego até o ambiente de testes |
| Sistema operacional | Debian Linux | Host da aplicação |
| Web server | Apache | Atendimento das requisições web |
| Aplicação | PHP / Laravel | Backend e interface do CRM |
| Banco de dados | PostgreSQL | Persistência dos dados |
| Cache / filas | Redis | Cache e suporte a filas conforme configuração |

## Princípios de segurança adotados

### Exposição mínima

O ambiente foi pensado para evitar publicação direta de serviços internos. PostgreSQL e Redis devem permanecer acessíveis apenas pelo servidor/aplicação ou por redes administrativas autorizadas.

Não devem ser publicados diretamente na internet:

- PostgreSQL;
- Redis;
- portas administrativas internas;
- interfaces de observabilidade ainda não preparadas para acesso externo;
- arquivos `.env`, credenciais, tokens ou chaves.

### HTTPS e proxies

Como a aplicação está atrás de uma camada intermediária, é importante que o Laravel reconheça corretamente o protocolo e os proxies confiáveis utilizados no ambiente.

As validações desta fase incluem:

- geração correta de URLs HTTPS;
- cookies e sessões funcionando atrás do proxy;
- redirecionamentos sem loops HTTP/HTTPS;
- headers encaminhados somente por proxies confiáveis;
- comportamento correto de login e logout;
- proteção CSRF preservada.

### Credenciais

Nenhuma credencial real do Cloudflare Tunnel, banco de dados, Redis ou aplicação deve ser armazenada no repositório.

Tokens, arquivos de credenciais e variáveis sensíveis devem permanecer fora do Git e ser gerenciados no ambiente onde o serviço é executado.

## O que está sendo validado

Durante esta etapa, o acesso externo é utilizado para testar:

- abertura do CRM fora da rede local;
- autenticação e sessão;
- navegação entre os módulos;
- comportamento do frontend em diferentes dispositivos;
- desempenho básico e tempo de resposta;
- importação e consultas realizadas pela aplicação;
- redirecionamentos HTTPS;
- logs de aplicação e infraestrutura;
- estabilidade do túnel;
- comportamento após reinício dos serviços;
- pontos que exigem hardening adicional.

## Estado atual

O acesso externo está sendo utilizado como **ambiente de testes e validação**.

Ainda não é considerado deploy definitivo de produção. A publicação em produção depende de uma revisão completa de infraestrutura, segurança, disponibilidade, backup, observabilidade e operação.

## Antes da produção

A evolução para produção deve incluir, entre outros pontos:

- hardening do Debian;
- política restritiva de firewall;
- revisão de usuários e privilégios;
- SSH com acesso controlado e autenticação forte;
- HTTPS e cookies seguros validados no domínio definitivo;
- revisão de proxies confiáveis;
- backups automáticos e teste de restore;
- estratégia de atualização e rollback;
- workers e scheduler supervisionados;
- monitoramento de disponibilidade e recursos;
- centralização e retenção de logs;
- alertas operacionais;
- validação de capacidade;
- revisão de dependências e vulnerabilidades;
- testes de regressão antes do go-live.

## Relação com a segurança da aplicação

Esta etapa complementa o hardening implementado no projeto, mas não o substitui.

Os controles de aplicação continuam sendo necessários independentemente do uso de Cloudflare Tunnel, incluindo autenticação, RBAC, Policies/Gates, proteção contra IDOR, CSRF, rate limiting, headers de segurança, tratamento de sessões, sanitização de logs e proteção do fluxo de importação.

A documentação geral desses controles está em:

- [`docs/architecture/SECURITY.md`](../architecture/SECURITY.md)

## Resultado da etapa

A principal evolução desta fase é sair de um ambiente exclusivamente local para um cenário de acesso externo controlado, permitindo validar a aplicação em condições mais próximas do uso real sem tratar o servidor de testes como ambiente de produção.

O aprendizado central é simples: **colocar uma aplicação acessível na internet é apenas uma parte do trabalho; publicar com segurança exige arquitetura, validação, observabilidade e preparação operacional.**

---

**Status:** em testes e validação externa.
