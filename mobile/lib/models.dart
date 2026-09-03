class VolunteerProfile {
  VolunteerProfile({
    required this.id,
    required this.name,
    required this.username,
    required this.email,
    required this.phone,
    required this.cityId,
    required this.cityName,
    required this.language,
    required this.imageUrl,
  });

  final int id;
  final String name;
  final String username;
  final String email;
  final String phone;
  final int? cityId;
  final String? cityName;
  final String? language;
  final String? imageUrl;

  factory VolunteerProfile.fromJson(Map<String, dynamic> json) {
    return VolunteerProfile(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? '',
      username: json['username']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      phone: json['phone']?.toString() ?? '',
      cityId: json['city_id'] == null ? null : int.tryParse(json['city_id'].toString()),
      cityName: json['city_name']?.toString(),
      language: json['language']?.toString(),
      imageUrl: json['image_url']?.toString(),
    );
  }
}

class ActivityItem {
  ActivityItem({
    required this.id,
    required this.name,
    required this.organisation,
    required this.cityId,
    required this.cityName,
    required this.dateFrom,
    required this.dateTo,
    required this.hours,
    required this.description,
    required this.requiredFiles,
    required this.transportation,
    required this.residency,
    required this.expenses,
    required this.training,
    required this.imageUrl,
    required this.isEnrolled,
    required this.enrollmentId,
    required this.enrollmentStatus,
    required this.latitude,
    required this.longitude,
  });

  final int id;
  final String name;
  final String organisation;
  final int? cityId;
  final String? cityName;
  final String? dateFrom;
  final String? dateTo;
  final int? hours;
  final String description;
  final String requiredFiles;
  final bool transportation;
  final bool residency;
  final bool expenses;
  final bool training;
  final String? imageUrl;
  final bool isEnrolled;
  final int? enrollmentId;
  final int? enrollmentStatus;
  final double? latitude;
  final double? longitude;

  factory ActivityItem.fromJson(Map<String, dynamic> json) {
    bool asBool(dynamic value) {
      if (value == null) return false;
      if (value is bool) return value;
      return value.toString() == '1' || value.toString().toLowerCase() == 'true';
    }

    return ActivityItem(
      id: int.tryParse(json['id']?.toString() ?? '') ?? 0,
      name: json['name']?.toString() ?? '',
      organisation: json['organisation']?.toString() ?? '',
      cityId: json['city_id'] == null ? null : int.tryParse(json['city_id'].toString()),
      cityName: json['city_name']?.toString(),
      dateFrom: json['date_from']?.toString(),
      dateTo: json['date_to']?.toString(),
      hours: json['hours'] == null ? null : int.tryParse(json['hours'].toString()),
      description: json['description']?.toString() ?? '',
      requiredFiles: json['required_files']?.toString() ?? '',
      transportation: asBool(json['transportation']),
      residency: asBool(json['residency']),
      expenses: asBool(json['expenses']),
      training: asBool(json['training']),
      imageUrl: json['image_url']?.toString(),
      isEnrolled: asBool(json['is_enrolled']),
      enrollmentId: json['enrollment_id'] == null ? null : int.tryParse(json['enrollment_id'].toString()),
      enrollmentStatus: json['enrollment_status'] == null ? null : int.tryParse(json['enrollment_status'].toString()),
      latitude: double.tryParse((json['latitude'] ?? json['lat'] ?? json['city_latitude'])?.toString() ?? ''),
      longitude: double.tryParse((json['longitude'] ?? json['lng'] ?? json['city_longitude'])?.toString() ?? ''),
    );
  }
}

class MobileStats {
  MobileStats({
    required this.totalActivities,
    required this.approvedActivities,
    required this.completedActivities,
    required this.totalVolunteers,
    required this.totalCities,
    required this.totalCertificates,
  });

  final int totalActivities;
  final int approvedActivities;
  final int completedActivities;
  final int totalVolunteers;
  final int totalCities;
  final int totalCertificates;

  factory MobileStats.fromJson(Map<String, dynamic> json) {
    return MobileStats(
      totalActivities: int.tryParse(json['total_activities']?.toString() ?? '') ?? 0,
      approvedActivities: int.tryParse(json['approved_activities']?.toString() ?? '') ?? 0,
      completedActivities: int.tryParse(json['completed_activities']?.toString() ?? '') ?? 0,
      totalVolunteers: int.tryParse(json['total_volunteers']?.toString() ?? '') ?? 0,
      totalCities: int.tryParse(json['total_cities']?.toString() ?? '') ?? 0,
      totalCertificates: int.tryParse(json['total_certificates']?.toString() ?? '') ?? 0,
    );
  }
}
