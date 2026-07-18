import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/avatar_editor_provider.dart';
import '../avatar_renderer.dart';
import '../../domain/avatar.dart';
import '../../domain/avatar_parts.dart';

class AvatarEditorPage extends ConsumerStatefulWidget {
  final String avatarId;
  final OikosAvatar? initialAvatar;
  final Function(OikosAvatar) onSave;

  const AvatarEditorPage({
    super.key,
    required this.avatarId,
    this.initialAvatar,
    required this.onSave,
  });

  @override
  ConsumerState<AvatarEditorPage> createState() => _AvatarEditorPageState();
}

class _AvatarEditorPageState extends ConsumerState<AvatarEditorPage> {
  int _currentTab = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (widget.initialAvatar != null) {
        ref.read(avatarEditorProvider(widget.avatarId).notifier).loadInitialAvatar(widget.initialAvatar!);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final avatar = ref.watch(avatarEditorProvider(widget.avatarId));

    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: const Text('Montar Avatar'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: [
          TextButton(
            onPressed: () {
              widget.onSave(avatar);
              Navigator.pop(context);
            },
            child: const Text('SALVAR', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
      body: Column(
        children: [
          // Área de Preview Superior
          Expanded(
            flex: 4,
            child: Center(
              child: SizedBox(
                width: 200,
                height: 300,
                child: OikosAvatarRenderer(avatar: avatar),
              ),
            ),
          ),
          
          // Controles de Edição Inferiores
          Expanded(
            flex: 6,
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(32)),
                boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 10, offset: Offset(0, -2))],
              ),
              child: Column(
                children: [
                  _buildTabBar(),
                  Expanded(
                    child: _buildTabContent(avatar),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTabBar() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          _buildTabIcon(Icons.face, 0),
          _buildTabIcon(Icons.remove_red_eye, 1),
          _buildTabIcon(Icons.cut, 2),
          _buildTabIcon(Icons.checkroom, 3),
        ],
      ),
    );
  }

  Widget _buildTabIcon(IconData icon, int index) {
    final isSelected = _currentTab == index;
    return GestureDetector(
      onTap: () => setState(() => _currentTab = index),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isSelected ? Colors.blue.withValues(alpha: 0.1) : Colors.transparent,
          shape: BoxShape.circle,
        ),
        child: Icon(
          icon,
          color: isSelected ? Colors.blue : Colors.grey,
          size: 28,
        ),
      ),
    );
  }

  Widget _buildTabContent(OikosAvatar avatar) {
    switch (_currentTab) {
      case 0: // Rosto & Pele
        return _buildOptionsGrid<HeadType>(
          title: 'Formato do Rosto',
          items: HeadType.values,
          selected: avatar.head,
          onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateHead(type),
          extra: _buildColorPalette(
            title: 'Cor da Pele',
            colors: [const Color(0xFFFFE0BD), const Color(0xFFFFCD94), const Color(0xFFEAC086), const Color(0xFFFFAD60), const Color(0xFF8D5524), const Color(0xFF4A3B32)],
            selected: avatar.theme.skinColor,
            onSelect: (c) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateSkinColor(c),
          ),
        );
      case 1: // Olhos & Boca
        return Column(
          children: [
            Expanded(
              child: _buildOptionsGrid<EyeType>(
                title: 'Olhos',
                items: EyeType.values,
                selected: avatar.eyes,
                onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateEyes(type),
              ),
            ),
            Expanded(
              child: _buildOptionsGrid<MouthType>(
                title: 'Boca',
                items: MouthType.values,
                selected: avatar.mouth,
                onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateMouth(type),
              ),
            ),
          ],
        );
      case 2: // Cabelo
        return _buildOptionsGrid<HairType>(
          title: 'Cabelo',
          items: HairType.values,
          selected: avatar.hair,
          onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateHair(type),
          extra: _buildColorPalette(
            title: 'Cor do Cabelo',
            colors: [const Color(0xFF000000), const Color(0xFF4A3B32), const Color(0xFF8B4513), const Color(0xFFF5DEB3), const Color(0xFFFF69B4), const Color(0xFF2196F3)],
            selected: avatar.theme.hairColor,
            onSelect: (c) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateHairColor(c),
          ),
        );
      case 3: // Roupas
        return _buildOptionsGrid<ShirtType>(
          title: 'Camisa',
          items: ShirtType.values,
          selected: avatar.shirt,
          onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateShirt(type),
          extra: _buildColorPalette(
            title: 'Cor da Camisa',
            colors: [const Color(0xFF88B04B), const Color(0xFFF44336), const Color(0xFF2196F3), const Color(0xFFFFEB3B), const Color(0xFF9C27B0), const Color(0xFFFFFFFF)],
            selected: avatar.theme.shirtColor,
            onSelect: (c) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateShirtColor(c),
          ),
        );
      default:
        return const SizedBox();
    }
  }

  Widget _buildOptionsGrid<T extends Enum>({
    required String title,
    required List<T> items,
    required T selected,
    required Function(T) onSelect,
    Widget? extra,
  }) {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 12),
          SizedBox(
            height: 60,
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                final isSelected = item == selected;
                return GestureDetector(
                  onTap: () => onSelect(item),
                  child: AnimatedContainer(
                    duration: const Duration(milliseconds: 200),
                    margin: const EdgeInsets.only(right: 12),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    decoration: BoxDecoration(
                      color: isSelected ? Colors.blue.withValues(alpha: 0.1) : Colors.grey.withValues(alpha: 0.1),
                      border: Border.all(color: isSelected ? Colors.blue : Colors.transparent, width: 2),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Center(
                      child: Text(
                        item.name.replaceAll(RegExp(r'\d+'), '').toUpperCase(), // Simplifica o nome (ex: short01 -> SHORT)
                        style: TextStyle(
                          color: isSelected ? Colors.blue : Colors.black54,
                          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
          if (extra != null) ...[
            const SizedBox(height: 24),
            extra,
          ]
        ],
      ),
    );
  }

  Widget _buildColorPalette({
    required String title,
    required List<Color> colors,
    required Color selected,
    required Function(Color) onSelect,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.grey)),
        const SizedBox(height: 12),
        SizedBox(
          height: 50,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: colors.length,
            itemBuilder: (context, index) {
              final color = colors[index];
              final isSelected = color.value == selected.value;
              return GestureDetector(
                onTap: () => onSelect(color),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  margin: const EdgeInsets.only(right: 12),
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(
                    color: color,
                    shape: BoxShape.circle,
                    border: isSelected ? Border.all(color: Colors.blue, width: 3) : Border.all(color: Colors.black12, width: 1),
                    boxShadow: isSelected ? [BoxShadow(color: color.withValues(alpha: 0.4), blurRadius: 8, spreadRadius: 2)] : null,
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
