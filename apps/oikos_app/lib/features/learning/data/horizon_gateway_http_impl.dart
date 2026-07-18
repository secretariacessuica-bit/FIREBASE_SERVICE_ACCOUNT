import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../profiles/domain/entities/member_identity.dart';
import '../../profiles/domain/entities/profile_theme.dart';
import 'horizon_gateway.dart'; // Mantém o contrato abstrato

class HorizonGatewayHttpImpl implements HorizonGateway {
  final String baseUrl;
  final http.Client client;

  HorizonGatewayHttpImpl({
    this.baseUrl = 'http://localhost:8080/api', // Endereço do Spring Boot
    http.Client? client,
  }) : client = client ?? http.Client();

  @override
  Future<List<DailyMission>> getDailyPacing(String memberId, {ProfileTheme theme = ProfileTheme.gamified}) async {
    try {
      final response = await client.get(
        Uri.parse('$baseUrl/missions/$memberId'),
        headers: {
          'Content-Type': 'application/json',
          'X-Profile-Theme': theme.toString(),
        },
      ).timeout(const Duration(seconds: 5));

      if (response.statusCode == 200) {
        final List<dynamic> data = json.decode(response.body);
        return data.map((json) => DailyMission.fromJson(json)).toList();
      } else {
        throw Exception("Falha ao buscar missões: ${response.statusCode}");
      }
    } catch (e) {
      // Fallback para não quebrar o app se o servidor Java estiver offline
      return _getFallbackMissions(theme);
    }
  }

  // Se o backend Java estiver offline, retorna missões em cache/hardcoded para demonstração
  List<DailyMission> _getFallbackMissions(ProfileTheme theme) {
    if (theme == ProfileTheme.playful) {
      return const [
        DailyMission(id: 'm1', title: 'Resgate as Cores!', type: 'vocabulary', xpReward: 50),
        DailyMission(id: 'm2', title: 'Música Mágica', type: 'speaking', xpReward: 30),
      ];
    } else if (theme == ProfileTheme.gamified) {
      return const [
        DailyMission(id: 'm3', title: 'Batalha de Verbos', type: 'grammar', xpReward: 100),
        DailyMission(id: 'm4', title: 'Ofensiva Ninja', type: 'streak', xpReward: 50),
      ];
    } else {
      return const [
        DailyMission(id: 'm5', title: 'Revisão Espaçada: Business', type: 'vocabulary', xpReward: 20),
        DailyMission(id: 'm6', title: 'Gramática Prática', type: 'grammar', xpReward: 20),
      ];
    }
  }
}
