
# Model Relationships

AvelPress supports the following Eloquent-style relationships:

## One to One

```php
use AvelPress\Database\Eloquent\Relations\HasOne;
use AvelPress\Database\Eloquent\Relations\BelongsTo;

class User extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }
}

class Profile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## One to Many

```php
use AvelPress\Database\Eloquent\Relations\HasMany;
use AvelPress\Database\Eloquent\Relations\BelongsTo;

class User extends Model
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## Many to Many

```php
use AvelPress\Database\Eloquent\Relations\BelongsToMany;

class User extends Model
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}

class Role extends Model
{
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
```

> **Note:** Always specify the correct return type (`HasOne`, `HasMany`, `BelongsTo`, `BelongsToMany`) in your relationship methods.

## Usage Example

```php
$user = User::find(1);
$profile = $user->profile; // One to one
$posts = $user->posts;     // One to many
$roles = $user->roles;     // Many to many
```

For more details, see the [Eloquent Models Guide](/guide/models/eloquent.md).

## Has Many Through

Access distant relationships through intermediate models.

```php
class Country extends Model
{
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function posts()
    {
        return $this->hasManyThrough(Post::class, User::class);
    }
}

class User extends Model
{
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Usage
$country = Country::find(1);
$posts = $country->posts; // All posts from users in this country
```

## Polymorphic Relationships

Allow a model to belong to more than one other model on a single association.

### One to Many Polymorphic

```php
class Comment extends Model
{
    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Video extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
```

### Using Polymorphic Relationships

```php
// Create comments for different models
$post = Post::find(1);
$post->comments()->create(['content' => 'Great post!']);

$video = Video::find(1);
$video->comments()->create(['content' => 'Nice video!']);

// Access the parent model
$comment = Comment::find(1);
$commentable = $comment->commentable; // Could be Post or Video

// Check the type
if ($comment->commentable_type === 'App\\Models\\Post') {
    // It's a post comment
}
```



## Eager Loading

Prevent N+1 query problems by loading relationships efficiently.

### Basic Eager Loading

```php
// N+1 problem (bad)
$users = User::all();
foreach ($users as $user) {
    echo $user->profile->bio; // Executes a query for each user
}

// Eager loading (good)
$users = User::with('profile')->get();
foreach ($users as $user) {
    echo $user->profile->bio; // Profile already loaded
}
```

### Multiple Relationships

```php
// Load multiple relationships
$users = User::with(['posts', 'profile', 'roles'])->get();

// Nested relationships
$users = User::with('posts.comments')->get();

// Mixed relationships
$users = User::with(['posts.comments.author', 'profile'])->get();
```

### Conditional Eager Loading

```php
// Load posts with conditions
$users = User::with(['posts' => function ($query) {
    $query->where('status', 'published')
          ->orderBy('created_at', 'desc');
}])->get();

// Load multiple relationships with conditions
$users = User::with([
    'posts' => function ($query) {
        $query->where('status', 'published');
    },
    'comments' => function ($query) {
        $query->where('approved', true);
    }
])->get();
```

### Lazy Eager Loading

Load relationships after the model is retrieved:

```php
$users = User::all();

// Later, load relationships
$users->load('posts');
$users->load(['posts.comments', 'profile']);

// With conditions
$users->load(['posts' => function ($query) {
    $query->where('status', 'published');
}]);
```

## Counting Related Models

```php
// Count related models
$users = User::withCount('posts')->get();
foreach ($users as $user) {
    echo $user->posts_count;
}

// Multiple counts
$users = User::withCount(['posts', 'comments'])->get();

// Conditional counts
$users = User::withCount([
    'posts',
    'posts as published_posts_count' => function ($query) {
        $query->where('status', 'published');
    }
])->get();

// Average, sum, max, min
$users = User::withAvg('posts', 'views')->get();
$users = User::withSum('orders', 'amount')->get();
```

## Querying Relationships

### Has Queries

```php
// Users who have posts
$users = User::has('posts')->get();

// Users who have at least 3 posts
$users = User::has('posts', '>=', 3)->get();

// Users who have published posts
$users = User::whereHas('posts', function ($query) {
    $query->where('status', 'published');
})->get();

// Users who don't have posts
$users = User::doesntHave('posts')->get();

// Users who don't have published posts
$users = User::whereDoesntHave('posts', function ($query) {
    $query->where('status', 'published');
})->get();
```

### Relationship Queries

```php
// Query through relationships
$posts = Post::whereRelation('user', 'status', 'active')->get();

// Or using joins
$posts = Post::join('users', 'posts.user_id', '=', 'users.id')
    ->where('users.status', 'active')
    ->select('posts.*')
    ->get();
```

## WordPress Integration

### WordPress User Relationships

```php
class User extends Model
{
    public function wordpressUser()
    {
        return $this->hasOne(WP_User::class, 'ID', 'wp_user_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Get WordPress posts
    public function wordpressPosts()
    {
        $wp_user = get_user_by('id', $this->wp_user_id);
        if ($wp_user) {
            return get_posts([
                'author' => $this->wp_user_id,
                'post_status' => 'any',
                'numberposts' => -1
            ]);
        }
        return [];
    }
}
```

### WordPress Post Relationships

```php
class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get WordPress post
    public function wordpressPost()
    {
        return get_post($this->wp_post_id);
    }

    // Get post meta
    public function getMeta($key, $single = true)
    {
        return get_post_meta($this->wp_post_id, $key, $single);
    }

    // WordPress categories (taxonomy)
    public function categories()
    {
        $wp_post = $this->wordpressPost();
        if ($wp_post) {
            return wp_get_post_categories($this->wp_post_id, ['fields' => 'all']);
        }
        return [];
    }
}
```

### Custom WordPress Relationships

```php
class Product extends Model
{
    // Related WordPress posts
    public function relatedPosts()
    {
        $related_ids = $this->getMeta('related_post_ids');
        if ($related_ids) {
            return get_posts([
                'include' => $related_ids,
                'post_type' => 'post',
                'post_status' => 'publish'
            ]);
        }
        return [];
    }

    // WooCommerce integration
    public function woocommerceProduct()
    {
        if ($this->wc_product_id) {
            return wc_get_product($this->wc_product_id);
        }
        return null;
    }
}
```

## Performance Tips

### Optimize Eager Loading

```php
// Load only needed columns
$users = User::with(['posts:id,user_id,title,status'])->get();

// Use select to limit columns on main model too
$users = User::select(['id', 'name', 'email'])
    ->with(['posts:id,user_id,title'])
    ->get();
```

### Use Constraints Wisely

```php
// Instead of loading all posts and filtering in PHP
$users = User::with('posts')->get();
$publishedPosts = $users->flatMap(function ($user) {
    return $user->posts->where('status', 'published');
});

// Load only published posts
$users = User::with(['posts' => function ($query) {
    $query->where('status', 'published');
}])->get();
```

### Consider Chunking

```php
// For large datasets, use chunking with relationships
User::with('posts')->chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process user and their posts
    }
});
```
