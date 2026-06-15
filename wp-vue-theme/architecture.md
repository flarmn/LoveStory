# LoveStory Vue Architecture

## Purpose

This document describes the planned Vue 3 architecture for the LoveStory modernization project.

The objective is to gradually replace frontend functionality implemented with PHP templates and jQuery while preserving compatibility with the existing WordPress backend and custom profile plugin.

---

# Phase 1: Profile Search

## Why Profile Search?

Profile Search is one of the most frequently used features in the application.

It is relatively isolated from the rest of the system and can be migrated without affecting messaging, favorites or profile management functionality.

This makes it an ideal starting point for Vue integration.

---

# Component Structure

```text
ProfileSearch
│
├── SearchFilters.vue
├── ProfileList.vue
├── ProfileCard.vue
├── Pagination.vue
└── profileSearchStore.js
```

---

## SearchFilters.vue

Responsibilities:

* Search form
* Gender filter
* Age range filter
* Country filter
* Additional search parameters

This component updates search state but does not directly load profiles.

---

## ProfileList.vue

Responsibilities:

* Request profile data from store
* Render search results
* Display loading state
* Display empty result state

Uses ProfileCard components for rendering.

---

## ProfileCard.vue

Responsibilities:

* Display profile photo
* Display user information
* Display profile summary
* Link to profile page

This component should remain presentation-only.

---

## Pagination.vue

Responsibilities:

* Page navigation
* Current page indication
* Previous / Next actions

Pagination updates search state and triggers profile reload.

---

# State Management

## profileSearchStore.js

Pinia store responsible for:

* Search parameters
* Current page
* Search results
* Total results count
* Loading state
* API requests

Store becomes the single source of truth for profile search functionality.

---

# Data Flow

```text
SearchFilters
        │
        ▼
profileSearchStore
        │
        ▼
WordPress REST API
        │
        ▼
profileSearchStore
        │
        ▼
ProfileList
        │
        ▼
ProfileCard
```

---

# API Layer

Future structure:

```text
src/
│
├── api/
│   └── profilesApi.js
│
├── stores/
│   └── profileSearchStore.js
│
└── components/
```

profilesApi.js will be responsible only for communication with backend endpoints.

Business logic should remain inside stores.

---

# Design Principles

## Single Responsibility

Each component should perform one task only.

## Reusability

Components should be reusable whenever possible.

## Centralized State

Shared state should be stored in Pinia.

## Separation of Concerns

* UI → Components
* State → Stores
* Network → API Layer

---

# Future Modules

Phase 2

* User Profile

Phase 3

* Favorites

Phase 4

* Messaging

Phase 5

* Notifications

Phase 6

* Real-time features

---

# Current Status

Architecture planning completed.

Implementation has not started yet.
