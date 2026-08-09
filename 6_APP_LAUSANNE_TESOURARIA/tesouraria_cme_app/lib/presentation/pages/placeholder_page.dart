import 'package:flutter/material.dart';
import '../widgets/app_sidebar_drawer.dart';

class PlaceholderPage extends StatelessWidget {
  final String title;
  final String route;
  final IconData icon;
  final String description;

  const PlaceholderPage({
    super.key,
    required this.title,
    required this.route,
    required this.icon,
    required this.description,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isDesktop = screenWidth > 800;

    Widget bodyContent = Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(28),
            decoration: BoxDecoration(
              color: const Color(0xFF1E3A8A).withValues(alpha: 0.08),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 56, color: const Color(0xFF1E3A8A)),
          ),
          const SizedBox(height: 24),
          Text(
            title,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: Color(0xFF0F172A),
            ),
          ),
          const SizedBox(height: 8),
          Text(
            description,
            style: const TextStyle(fontSize: 14, color: Color(0xFF64748B)),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 32),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            decoration: BoxDecoration(
              color: const Color(0xFFFFFBEB),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFF59E0B)),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.construction_rounded, size: 16, color: Color(0xFFF59E0B)),
                SizedBox(width: 8),
                Text(
                  'Em desenvolvimento',
                  style: TextStyle(
                    color: Color(0xFFB45309),
                    fontWeight: FontWeight.w600,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );

    if (isDesktop) {
      bodyContent = Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          AppSidebarDrawer(activeRoute: route, permanent: true),
          Expanded(child: bodyContent),
        ],
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFFFAFAFA),
      drawer: isDesktop ? null : AppSidebarDrawer(activeRoute: route),
      appBar: isDesktop
          ? null
          : AppBar(
              backgroundColor: Colors.white,
              foregroundColor: const Color(0xFF0F172A),
              elevation: 0,
              shape: const Border(bottom: BorderSide(color: Color(0xFFE5E7EB), width: 1)),
              title: Text(
                title,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
            ),
      body: bodyContent,
    );
  }
}
