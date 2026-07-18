import 'package:flutter/material.dart';

class SceneObject {
  final String id;
  final String temporaryAsset; // e.g., '📖', '🎤'
  final String semanticLabel;
  final VoidCallback onTap;

  SceneObject({
    required this.id,
    required this.temporaryAsset,
    required this.semanticLabel,
    required this.onTap,
  });
}
