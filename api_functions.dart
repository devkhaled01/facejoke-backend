import 'dart:convert';
import 'package:http/http.dart' as http;

// --- Configuration & Models ---

// IMPORTANT: Replace with your actual API base URL.
const String API_BASE_URL = 'http://10.0.2.2:8000/api/v1';

// IMPORTANT: Implement this function to securely retrieve the user's auth token.
Future<String?> getAuthToken() async {
  // Example: return await secureStorage.read(key: 'auth_token');
  // For now, returning a placeholder:
  return 'YOUR_AUTH_TOKEN';
}

// NOTE: You should have Post and Reaction models defined in your project.
// Below are placeholder examples. Update them to match your actual models.

class Post {
  final String id;
  final String? content;
  // Add other fields like 'userId', 'mediaUrl', 'type', etc.

  Post({required this.id, this.content});

  factory Post.fromJson(Map<String, dynamic> json) {
    return Post(
      id: json['id'],
      content: json['content'],
    );
  }
}

class Reaction {
  final String id;
  final String content;
  // Add other fields like 'userId', 'type', 'post', etc.

  Reaction({required this.id, required this.content});

  factory Reaction.fromJson(Map<String, dynamic> json) {
    return Reaction(
      id: json['id'],
      content: json['content'],
    );
  }
}

// --- API Functions ---

/// Deletes a post by its ID.
///
/// Throws an exception if the API call fails.
Future<void> deletePost(String postId) async {
  final token = await getAuthToken();
  final url = Uri.parse('$API_BASE_URL/posts/$postId');

  final response = await http.delete(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode != 204) {
    String errorMessage = 'Failed to delete post.';
    try {
      final responseBody = json.decode(response.body);
      errorMessage = responseBody['message'] ?? errorMessage;
    } catch (_) {}
    throw Exception('$errorMessage (Status Code: ${response.statusCode})');
  }
}

/// Fetches a paginated list of posts for a specific user.
///
/// Returns a list of [Post] objects.
/// Throws an exception if the API call fails.
Future<List<Post>> getUserPosts(String userId) async {
  final url = Uri.parse('$API_BASE_URL/users/$userId/posts');

  final response = await http.get(url, headers: {'Accept': 'application/json'});

  if (response.statusCode == 200) {
    final responseBody = json.decode(response.body);
    final List<dynamic> postData = responseBody['data'];
    return postData.map((json) => Post.fromJson(json)).toList();
  } else {
    String errorMessage = 'Failed to fetch user posts.';
    try {
      final responseBody = json.decode(response.body);
      errorMessage = responseBody['message'] ?? errorMessage;
    } catch (_) {}
    throw Exception('$errorMessage (Status Code: ${response.statusCode})');
  }
}

/// Fetches a paginated list of reactions for a specific user.
///
/// Returns a list of [Reaction] objects.
/// Throws an exception if the API call fails.
Future<List<Reaction>> getUserReactions(String userId) async {
  final url = Uri.parse('$API_BASE_URL/users/$userId/reactions');

  final response = await http.get(url, headers: {'Accept': 'application/json'});

  if (response.statusCode == 200) {
    final responseBody = json.decode(response.body);
    final List<dynamic> reactionData = responseBody['data'];
    return reactionData.map((json) => Reaction.fromJson(json)).toList();
  } else {
    String errorMessage = 'Failed to fetch user reactions.';
    try {
      final responseBody = json.decode(response.body);
      errorMessage = responseBody['message'] ?? errorMessage;
    } catch (_) {}
    throw Exception('$errorMessage (Status Code: ${response.statusCode})');
  }
}

/// Deletes a reaction by its ID.
///
/// Throws an exception if the API call fails.
Future<void> deleteReaction(String reactionId) async {
  final token = await getAuthToken();
  final url = Uri.parse('$API_BASE_URL/reactions/$reactionId');

  final response = await http.delete(
    url,
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );

  if (response.statusCode != 204) {
    String errorMessage = 'Failed to delete reaction.';
    try {
      final responseBody = json.decode(response.body);
      errorMessage = responseBody['message'] ?? errorMessage;
    } catch (_) {}
    throw Exception('$errorMessage (Status Code: ${response.statusCode})');
  }
}
