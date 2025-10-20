# Chat-System

Aplicação de chat em tempo real construída com Laravel (backend) e Inertia + Vue (frontend).

## Resumo (para partilhar)

- Salas de chat e mensagens diretas
- Convites para salas e gestão de membros (pivot `room_user`)
- Estrutura pronta para adicionar broadcasting (realtime)

## O que está no repositório

- Migrations e modelos Eloquent (Users, Rooms, Messages, Invitations)
- Seeders de exemplo para desenvolvimento
- Frontend com Inertia + Vue e componentes básicos de chat
- Vite com `@laravel/vite-plugin-wayfinder` configurado

1. Instala dependências:

```powershell
composer install
npm install
```

3. Executa migrações (local):

```powershell
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
```

4. Frontend:

```powershell
npm run dev    # desenvolvimento (HMR)
npm run build  # produção
```
