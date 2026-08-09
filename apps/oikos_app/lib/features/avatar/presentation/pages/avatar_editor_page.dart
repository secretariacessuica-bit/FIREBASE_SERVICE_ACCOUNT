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
          _buildTabIcon(Icons.face, 0), // Corpo & Pele
          _buildTabIcon(Icons.mood, 1), // Expressão
          _buildTabIcon(Icons.cut, 2), // Cabelo
          _buildTabIcon(Icons.checkroom, 3), // Roupas (Camisas & Calças)
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
      case 0: // Corpo & Pele
        return Padding(
          padding: const EdgeInsets.all(16.0),
          child: _buildColorPalette(
            title: 'Cor da Pele',
            colors: const [Color(0xFFFFCD94), Color(0xFFEAC086), Color(0xFF8D5524), Color(0xFF4A3B32)],
            selected: avatar.theme.skinColor,
            onSelect: (c) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateSkinColor(c),
          ),
        );
      case 1: // Expressão (Olhos + Boca Combinados)
        return _buildOptionsGrid<MouthType>(
          title: 'Expressão Facial',
          items: MouthType.values,
          selected: avatar.activeMouth,
          labels: const {
            MouthType.face01: 'ROSTO 1',
            MouthType.face02: 'ROSTO 2',
            MouthType.face03: 'ROSTO 3',
            MouthType.face04: 'ROSTO 4',
            MouthType.face05: 'ROSTO 5',
            MouthType.face06: 'ROSTO 6',
            MouthType.face07: 'ROSTO 7',
            MouthType.face08: 'ROSTO 8',
            MouthType.face09: 'ROSTO 9',
            MouthType.face10: 'ROSTO 10',
            MouthType.face11: 'ROSTO 11',
            MouthType.face12: 'ROSTO 12',
          },
          onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateMouth(type),
        );
      case 2: // Cabelo
        return _buildOptionsGrid<HairType>(
          title: 'Estilo de Cabelo',
          items: HairType.values,
          selected: avatar.hair,
          labels: const {
            HairType.none: 'NENHUM',
            HairType.short01: 'CURTO 1',
            HairType.short02: 'CURTO 2',
            HairType.long01: 'LONGO 1',
            HairType.long02: 'LONGO 2',
            HairType.bun: 'COQUE',
          },
          onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateHair(type),
          extra: _buildColorPalette(
            title: 'Cor do Cabelo (Original)',
            colors: [const Color(0xFF000000), const Color(0xFF4A3B32), const Color(0xFF8B4513), const Color(0xFFF5DEB3), const Color(0xFFFF69B4), const Color(0xFF2196F3)],
            selected: avatar.theme.hairColor,
            onSelect: (c) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateHairColor(c),
          ),
        );
      case 3: // Roupas (Camisas & Calças)
        return ListView(
          shrinkWrap: true,
          physics: const ClampingScrollPhysics(),
          children: [
            _buildOptionsGrid<ShirtType>(
              title: 'Camisa / Agasalho',
              items: ShirtType.values,
              selected: avatar.shirt,
              labels: const {
                ShirtType.none: 'NENHUMA',
                ShirtType.basic: 'BÁSICA',
                ShirtType.hoodie: 'MOLETOM',
                ShirtType.jacket: 'JAQUETA',
              },
              onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateShirt(type),
              extra: _buildColorPalette(
                title: 'Cor da Roupa (Original)',
                colors: [const Color(0xFF88B04B), const Color(0xFFF44336), const Color(0xFF2196F3), const Color(0xFFFFEB3B), const Color(0xFF9C27B0), const Color(0xFFFFFFFF)],
                selected: avatar.theme.shirtColor,
                onSelect: (c) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updateShirtColor(c),
              ),
            ),
            const Divider(height: 32, thickness: 1, indent: 16, endIndent: 16),
            _buildOptionsGrid<PantsType>(
              title: 'Calça / Short',
              items: PantsType.values,
              selected: avatar.pants,
              labels: const {
                PantsType.none: 'NENHUMA',
                PantsType.pants01: 'JEANS COMPRIDO',
                PantsType.pants02: 'SHORT JEANS',
                PantsType.pants03: 'CALÇA BEGE',
                PantsType.pants04: 'CARGO VERDE',
                PantsType.pants05: 'JEANS DOBRADA',
                PantsType.pants06: 'CALÇA AZUL 2',
                PantsType.pants07: 'SHORT BEGE',
                PantsType.pants08: 'SHORT PRETO',
                PantsType.pants09: 'SHORT VERDE',
                PantsType.pants10: 'SHORT LARANJA',
              },
              onSelect: (type) => ref.read(avatarEditorProvider(widget.avatarId).notifier).updatePants(type),
            ),
            const SizedBox(height: 32),
          ],
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
    Map<T, String>? labels,
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
                final String displayName = labels != null && labels.containsKey(item)
                    ? labels[item]!
                    : item.name.replaceAll(RegExp(r'\d+'), '').toUpperCase();
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
                        displayName,
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
