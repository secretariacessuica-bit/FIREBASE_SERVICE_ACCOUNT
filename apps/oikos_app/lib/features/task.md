# Tarefas — Unificação do Lumo e Ajustes do Login

- [x] Modificar `living_scene_page.dart` (Criar `MissionStatusLabel`, ler do Hive, navegação com await/setState, remover `_PulseWidget`)
- [x] Modificar `exercise_page.dart` (Mudar textos da missão, remover "SITUAÇÃO" label/ícone, ajustar feedback de sucesso e loop de comemoração)
- [x] Refatorar `OikosAvatarRenderer` (`avatar_renderer.dart`) para usar o sistema profissional de camadas centralizadas registradas (Z-Index rigoroso, zero offset, AnimatedSwitcher)
- [x] Compilar a versão Web release (`flutter build web --release`)
- [x] Deploy Firebase Hosting (`npx.cmd firebase deploy --only hosting`)
