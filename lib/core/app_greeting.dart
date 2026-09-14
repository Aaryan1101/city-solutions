String appGreeting([DateTime? now]) {
  final hour = (now ?? DateTime.now()).hour;
  if (hour < 12) return 'Good morning';
  if (hour < 17) return 'Good afternoon';
  if (hour < 21) return 'Good evening';
  return 'Good night';
}

String appDisplayName(String? value) {
  final trimmed = (value ?? '').trim();
  if (trimmed.isEmpty) return 'Customer';
  final first = trimmed.split(RegExp(r'\s+')).first.trim();
  return first.isEmpty ? 'Customer' : first;
}
