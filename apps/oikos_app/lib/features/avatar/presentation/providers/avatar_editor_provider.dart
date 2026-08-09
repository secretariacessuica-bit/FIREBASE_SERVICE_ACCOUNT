import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../domain/avatar.dart';
import '../../domain/avatar_parts.dart';
import '../../domain/avatar_theme.dart';
import 'package:flutter/material.dart';

final avatarEditorProvider = StateNotifierProvider.family<AvatarEditorNotifier, OikosAvatar, String>((ref, id) {
  return AvatarEditorNotifier(id);
});

class AvatarEditorNotifier extends StateNotifier<OikosAvatar> {
  AvatarEditorNotifier(String id) : super(OikosAvatar.defaultAvatar(id));

  void loadInitialAvatar(OikosAvatar avatar) {
    state = avatar;
  }

  void updateHead(HeadType type) {
    state = OikosAvatar(
      id: state.id, head: type, eyes: state.eyes, eyebrow: state.eyebrow, mouth: state.mouth,
      hair: state.hair, shirt: state.shirt, pants: state.pants, shoes: state.shoes,
      accessory: state.accessory, theme: state.theme, heightScale: state.heightScale,
    );
  }

  void updateHair(HairType type) {
    state = OikosAvatar(
      id: state.id, head: state.head, eyes: state.eyes, eyebrow: state.eyebrow, mouth: state.mouth,
      hair: type, shirt: state.shirt, pants: state.pants, shoes: state.shoes,
      accessory: state.accessory, theme: state.theme, heightScale: state.heightScale,
    );
  }

  void updateEyes(EyeType type) {
    state = OikosAvatar(
      id: state.id, head: state.head, eyes: type, eyebrow: state.eyebrow, mouth: state.mouth,
      hair: state.hair, shirt: state.shirt, pants: state.pants, shoes: state.shoes,
      accessory: state.accessory, theme: state.theme, heightScale: state.heightScale,
    );
  }

  void updateMouth(MouthType type) {
    state = OikosAvatar(
      id: state.id, head: state.head, eyes: state.eyes, eyebrow: state.eyebrow, mouth: type,
      hair: state.hair, shirt: state.shirt, pants: state.pants, shoes: state.shoes,
      accessory: state.accessory, theme: state.theme, heightScale: state.heightScale,
    );
  }

  void updateShirt(ShirtType type) {
    state = OikosAvatar(
      id: state.id, head: state.head, eyes: state.eyes, eyebrow: state.eyebrow, mouth: state.mouth,
      hair: state.hair, shirt: type, pants: state.pants, shoes: state.shoes,
      accessory: state.accessory, theme: state.theme, heightScale: state.heightScale,
    );
  }

  void updatePants(PantsType type) {
    state = OikosAvatar(
      id: state.id, head: state.head, eyes: state.eyes, eyebrow: state.eyebrow, mouth: state.mouth,
      hair: state.hair, shirt: state.shirt, pants: type, shoes: state.shoes,
      accessory: state.accessory, theme: state.theme, heightScale: state.heightScale,
    );
  }

  void updateSkinColor(Color color) {
    final newTheme = AvatarTheme(
      skinColor: color, hairColor: state.theme.hairColor, shirtColor: state.theme.shirtColor,
      pantsColor: state.theme.pantsColor, shoeColor: state.theme.shoeColor,
    );
    state = OikosAvatar(
      id: state.id, head: state.head, eyes: state.eyes, eyebrow: state.eyebrow, mouth: state.mouth,
      hair: state.hair, shirt: state.shirt, pants: state.pants, shoes: state.shoes,
      accessory: state.accessory, theme: newTheme, heightScale: state.heightScale,
    );
  }

  void updateHairColor(Color color) {
    final newTheme = AvatarTheme(
      skinColor: state.theme.skinColor, hairColor: color, shirtColor: state.theme.shirtColor,
      pantsColor: state.theme.pantsColor, shoeColor: state.theme.shoeColor,
    );
    state = OikosAvatar(
      id: state.id, head: state.head, eyes: state.eyes, eyebrow: state.eyebrow, mouth: state.mouth,
      hair: state.hair, shirt: state.shirt, pants: state.pants, shoes: state.shoes,
      accessory: state.accessory, theme: newTheme, heightScale: state.heightScale,
    );
  }

  void updateShirtColor(Color color) {
    final newTheme = AvatarTheme(
      skinColor: state.theme.skinColor, hairColor: state.theme.hairColor, shirtColor: color,
      pantsColor: state.theme.pantsColor, shoeColor: state.theme.shoeColor,
    );
    state = OikosAvatar(
      id: state.id, head: state.head, eyes: state.eyes, eyebrow: state.eyebrow, mouth: state.mouth,
      hair: state.hair, shirt: state.shirt, pants: state.pants, shoes: state.shoes,
      accessory: state.accessory, theme: newTheme, heightScale: state.heightScale,
    );
  }
}
