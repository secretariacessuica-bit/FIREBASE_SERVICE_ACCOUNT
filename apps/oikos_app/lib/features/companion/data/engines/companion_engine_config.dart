enum CompanionEngineMode {
  disabled,
  mock,
  remote,
  fallback,
  testing,
}

class CompanionEngineConfig {
  final CompanionEngineMode mode;

  const CompanionEngineConfig({
    this.mode = CompanionEngineMode.mock,
  });
}
