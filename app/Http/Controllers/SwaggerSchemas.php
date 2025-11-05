<?php

namespace App\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="Trip",
 *     type="object",
 *     title="Trip",
 *     description="Trip model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Tokyo Adventure"),
 *     @OA\Property(property="destination_country", type="string", example="Japan"),
 *     @OA\Property(property="destination_city", type="string", example="Tokyo"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2025-12-07"),
 *     @OA\Property(property="status", type="string", enum={"planning", "ongoing", "completed"}, example="planning"),
 *     @OA\Property(property="is_group", type="boolean", example=false),
 *     @OA\Property(property="progress", type="string", nullable=true, example="Planning itinerary"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Place",
 *     type="object",
 *     title="Place",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="naver_place_id", type="string", example="1234567890"),
 *     @OA\Property(property="name", type="string", example="Tokyo Tower"),
 *     @OA\Property(property="address", type="string", example="4 Chome-2-8 Shibakoen, Minato City, Tokyo"),
 *     @OA\Property(property="latitude", type="number", format="double", example=35.6586),
 *     @OA\Property(property="longitude", type="number", format="double", example=139.7454),
 *     @OA\Property(property="category", type="string", example="Landmark"),
 *     @OA\Property(property="phone_number", type="string", nullable=true, example="+81-3-3433-5111"),
 *     @OA\Property(property="rating", type="number", format="double", nullable=true, example=4.5),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="MapCheckpoint",
 *     type="object",
 *     title="Map Checkpoint",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="place_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="name", type="string", example="Tokyo Tower Visit"),
 *     @OA\Property(property="latitude", type="number", format="double", example=35.6586),
 *     @OA\Property(property="longitude", type="number", format="double", example=139.7454),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Best view at sunset"),
 *     @OA\Property(property="checked_in", type="boolean", example=false),
 *     @OA\Property(property="checked_in_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     title="Review",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="reviewable_type", type="string", example="place"),
 *     @OA\Property(property="reviewable_id", type="integer", example=1),
 *     @OA\Property(property="rating", type="integer", example=5),
 *     @OA\Property(property="comment", type="string", nullable=true, example="Amazing view!"),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         description="Array of public image URLs",
 *         @OA\Items(type="string", format="url", example="http://tripplanner.test/storage/reviews/1/uuid.jpg")
 *     ),
 *     @OA\Property(property="is_flagged", type="boolean", example=false, description="Whether content is flagged by moderation"),
 *     @OA\Property(
 *         property="moderation_results",
 *         type="object",
 *         nullable=true,
 *         description="NAVER Green-Eye moderation results (only shown if flagged)",
 *         @OA\Property(property="safe", type="boolean", example=true),
 *         @OA\Property(property="reason", type="string", example="Content passed safety checks")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Translation",
 *     type="object",
 *     title="Translation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="source_type", type="string", enum={"text", "image", "speech"}, example="text"),
 *     @OA\Property(property="source_text", type="string", example="안녕하세요"),
 *     @OA\Property(property="translated_text", type="string", example="Hello"),
 *     @OA\Property(property="source_lang", type="string", example="ko"),
 *     @OA\Property(property="target_lang", type="string", example="en"),
 *     @OA\Property(property="file_path", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Tag",
 *     type="object",
 *     title="Tag",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Adventure"),
 *     @OA\Property(property="slug", type="string", example="adventure"),
 *     @OA\Property(property="usage_count", type="integer", example=15),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Favorite",
 *     type="object",
 *     title="Favorite",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="favoritable_type", type="string", example="App\\Models\\Place"),
 *     @OA\Property(property="favoritable_id", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Comment",
 *     type="object",
 *     title="Comment",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="entity_type", type="string", example="trip"),
 *     @OA\Property(property="entity_id", type="integer", example=5),
 *     @OA\Property(property="content", type="string", example="Great trip! Loved it."),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         description="Array of public image URLs",
 *         @OA\Items(type="string", format="url", example="http://tripplanner.test/storage/comments/1/uuid.jpg")
 *     ),
 *     @OA\Property(property="is_flagged", type="boolean", example=false, description="Whether content is flagged by moderation"),
 *     @OA\Property(
 *         property="moderation_results",
 *         type="object",
 *         nullable=true,
 *         description="NAVER Green-Eye moderation results (only shown if flagged)",
 *         @OA\Property(property="safe", type="boolean", example=true),
 *         @OA\Property(property="reason", type="string", example="Content passed safety checks")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="TripParticipant",
 *     type="object",
 *     title="Trip Participant",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="role", type="string", enum={"owner", "editor", "viewer"}, example="editor"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="TripDiary",
 *     type="object",
 *     title="Trip Diary",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="entry_date", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="text", type="string", example="Today was amazing!"),
 *     @OA\Property(property="mood", type="string", enum={"happy", "excited", "tired", "sad", "neutral"}, example="happy"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ChecklistItem",
 *     type="object",
 *     title="Checklist Item",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="Pack passport"),
 *     @OA\Property(property="is_checked", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ItineraryItem",
 *     type="object",
 *     title="Itinerary Item",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="day_number", type="integer", example=2),
 *     @OA\Property(property="title", type="string", example="Visit Tokyo Tower"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Enjoy panoramic views"),
 *     @OA\Property(property="start_time", type="string", format="time", nullable=true, example="09:00:00"),
 *     @OA\Property(property="end_time", type="string", format="time", nullable=true, example="11:00:00"),
 *     @OA\Property(property="place_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="duration_minutes", type="integer", nullable=true, example=120),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="CheckpointImage",
 *     type="object",
 *     title="Checkpoint Image",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="map_checkpoint_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="file_path", type="string", example="checkpoints/1/1/uuid.jpg"),
 *     @OA\Property(property="url", type="string", example="http://localhost/storage/checkpoints/1/1/uuid.jpg"),
 *     @OA\Property(property="caption", type="string", nullable=true, example="Beautiful view"),
 *     @OA\Property(property="is_flagged", type="boolean", example=false, description="Whether content is flagged by moderation"),
 *     @OA\Property(
 *         property="moderation_results",
 *         type="object",
 *         nullable=true,
 *         description="NAVER Green-Eye moderation results (only shown if flagged)",
 *         @OA\Property(property="safe", type="boolean", example=true),
 *         @OA\Property(property="reason", type="string", example="Content passed safety checks")
 *     ),
 *     @OA\Property(property="uploaded_at", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Share",
 *     type="object",
 *     title="Share",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="token", type="string", example="abc123xyz456..."),
 *     @OA\Property(property="permission", type="string", enum={"viewer", "editor"}, example="viewer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Notification",
 *     type="object",
 *     title="Notification",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"trip_invite", "comment_added", "check_in", "trip_shared", "participant_added", "review_added"}, example="trip_invite"),
 *     @OA\Property(property="data", type="object", example={"trip_id": 5, "inviter_name": "John"}),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Response(
 *     response="Unauthorized",
 *     description="Unauthenticated",
 *     @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
 * )
 *
 * @OA\Response(
 *     response="ValidationError",
 *     description="Validation error",
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 * )
 *
 * @OA\Response(
 *     response="Forbidden",
 *     description="Forbidden",
 *     @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
 * )
 *
 * @OA\Response(
 *     response="NotFound",
 *     description="Resource not found",
 *     @OA\JsonContent(ref="#/components/schemas/NotFoundError")
 * )
 */
class SwaggerSchemas
{
    // This class exists only to hold Swagger/OpenAPI schema definitions
    // It is never instantiated
}
