<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Intera Backend API",
 *     version="1.0.0",
 *     description="OpenAPI annotations for the Intera backend APIs."
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Same host as the backend application"
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication and token lifecycle endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Banner",
 *     description="Homepage banner content endpoints"
 * )
 *
 * @OA\Tag(
 *     name="FAQs",
 *     description="Frequently asked questions endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Plans",
 *     description="Plan catalogue and recommender endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Servers",
 *     description="Authenticated server management endpoints"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT bearer token returned by /api/auth/login and /api/auth/refresh."
 * )
 *
 * @OA\Schema(
 *     schema="ApiMessage",
 *     type="object",
 *     required={"message"},
 *     @OA\Property(property="message", type="string", example="Logged out.")
 * )
 *
 * @OA\Schema(
 *     schema="ApiValidationError",
 *     type="array",
 *     @OA\Items(type="string", example="The email field is required.")
 * )
 *
 * @OA\Schema(
 *     schema="ApiValidationErrors",
 *     type="object",
 *     required={"message", "errors"},
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=@OA\Schema(ref="#/components/schemas/ApiValidationError")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AuthUser",
 *     type="object",
 *     required={"id", "name", "email"},
 *     @OA\Property(property="id", type="integer", example=123),
 *     @OA\Property(property="username", type="string", nullable=true, example="player123"),
 *     @OA\Property(property="first_name", type="string", nullable=true, example="Alex"),
 *     @OA\Property(property="last_name", type="string", nullable=true, example="Doe"),
 *     @OA\Property(property="name", type="string", example="Alex Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="alex@example.com"),
 *     @OA\Property(property="role", type="string", nullable=true, example="customer")
 * )
 *
 * @OA\Schema(
 *     schema="AuthPayload",
 *     type="object",
 *     required={"token", "refresh_token", "token_type", "expires_in", "expires_at", "user"},
 *     @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *     @OA\Property(property="refresh_token", type="string", example="zGfJ1yR8zQq2RUDN7h4k..."),
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=3600),
 *     @OA\Property(property="expires_at", type="integer", example=1737462000),
 *     @OA\Property(property="user", ref="#/components/schemas/AuthUser")
 * )
 *
 * @OA\Schema(
 *     schema="FaqItem",
 *     type="object",
 *     required={"title", "content", "showOnHome"},
 *     @OA\Property(property="title", type="string", example="How fast is setup?"),
 *     @OA\Property(property="content", type="string", example="Most servers are ready within 5 minutes."),
 *     @OA\Property(property="showOnHome", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="PlanRecommenderOption",
 *     type="object",
 *     required={"label", "weight"},
 *     @OA\Property(property="label", type="string", example="10-20"),
 *     @OA\Property(property="weight", type="integer", example=2)
 * )
 *
 * @OA\Schema(
 *     schema="PlanRecommender",
 *     type="object",
 *     required={"players", "versions", "types"},
 *     @OA\Property(
 *         property="players",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PlanRecommenderOption")
 *     ),
 *     @OA\Property(
 *         property="versions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PlanRecommenderOption")
 *     ),
 *     @OA\Property(
 *         property="types",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/PlanRecommenderOption")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ModpackSummary",
 *     type="object",
 *     required={"slug", "name", "heading", "description", "headerClass", "startingPlan", "modId"},
 *     @OA\Property(property="slug", type="string", example="vaulthunters"),
 *     @OA\Property(property="name", type="string", example="Vault Hunters"),
 *     @OA\Property(property="heading", type="string", example="Get into the best RPG modpack in Minecraft"),
 *     @OA\Property(property="description", type="string", example="Launch your modpack server quickly."),
 *     @OA\Property(property="headerClass", type="string", example="shared-page"),
 *     @OA\Property(property="startingPlan", type="string", example="pog"),
 *     @OA\Property(property="modId", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="PublicPlan",
 *     type="object",
 *     required={"name", "title", "icon", "ram", "displayPrice", "bullets", "showDefaultPlans", "modpacks", "locations", "ribbon", "availability"},
 *     @OA\Property(property="name", type="string", example="parrot"),
 *     @OA\Property(property="title", type="string", example="Parrot"),
 *     @OA\Property(property="icon", type="string", example="parrot.png"),
 *     @OA\Property(property="ram", type="integer", example=1),
 *     @OA\Property(
 *         property="displayPrice",
 *         type="object",
 *         additionalProperties=@OA\Schema(type="number", format="float", minimum=0)
 *     ),
 *     @OA\Property(property="bullets", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="showDefaultPlans", type="boolean", example=true),
 *     @OA\Property(property="modpacks", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="locations", type="array", @OA\Items(type="string", example="de")),
 *     @OA\Property(property="ribbon", type="string", nullable=true, example="Most Popular"),
 *     @OA\Property(
 *         property="availability",
 *         type="object",
 *         additionalProperties=@OA\Schema(type="boolean")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="LocationOption",
 *     type="object",
 *     required={"key", "title", "flag"},
 *     @OA\Property(property="key", type="string", example="de"),
 *     @OA\Property(property="title", type="string", example="Germany (EU)"),
 *     @OA\Property(property="flag", type="string", example="de")
 * )
 *
 * @OA\Schema(
 *     schema="PlanRecommendation",
 *     type="object",
 *     required={"score", "plan"},
 *     @OA\Property(property="score", type="integer", example=6),
 *     @OA\Property(
 *         property="plan",
 *         type="object",
 *         required={"name", "title", "icon", "ram", "displayPrice", "ribbon"},
 *         @OA\Property(property="name", type="string", example="dolphin"),
 *         @OA\Property(property="title", type="string", example="Dolphin"),
 *         @OA\Property(property="icon", type="string", example="dolphin.png"),
 *         @OA\Property(property="ram", type="integer", example=5),
 *         @OA\Property(
 *             property="displayPrice",
 *             type="object",
 *             additionalProperties=@OA\Schema(type="number", format="float", minimum=0)
 *         ),
 *         @OA\Property(property="ribbon", type="string", nullable=true, example="Most Popular")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ServerPlanSummary",
 *     type="object",
 *     required={"name", "title", "ram"},
 *     @OA\Property(property="name", type="string", example="parrot"),
 *     @OA\Property(property="title", type="string", example="Parrot"),
 *     @OA\Property(property="ram", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="ServerSummary",
 *     type="object",
 *     required={"id", "uuid", "name", "suspended", "status", "stripe_tx_return", "plan"},
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="fdbf7b6f-1a44-482f-9883-3968d3fe1270"),
 *     @OA\Property(property="name", type="string", example="My SMP Server"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2026-02-25T18:15:00Z"),
 *     @OA\Property(property="suspended", type="boolean", example=false),
 *     @OA\Property(property="status", type="string", nullable=true, example="active"),
 *     @OA\Property(property="stripe_tx_return", type="boolean", example=true),
 *     @OA\Property(property="plan", ref="#/components/schemas/ServerPlanSummary", nullable=true)
 * )
 */
final class OpenApiSpec
{
}
