Nonces
======

[![Travis CI](https://img.shields.io/travis/Brain-WP/Nonces.svg?style=flat-square)](https://travis-ci.org/Brain-WP/Nonces)
[![codecov.io](https://img.shields.io/codecov/c/github/Brain-WP/Nonces.svg?style=flat-square)](https://codecov.io/github/Brain-WP/Nonces)
[![MIT license](https://img.shields.io/packagist/l/brain/nonces.svg?style=flat-square)](http://opensource.org/licenses/MIT)

------

** Nonces is an OOP package for WordPress to deal with nonces. **

-------------

TOC

- [Introduction](#Introduction)
- [How it works](#how-it-works)
    - [Rethinking WordPress workflow](#rethinking-wordpress-workflow)
    - [`NonceInterface` and `WpNonce`](#nonceinterface-and-wpnonce)
    - [Nonce context](#nonce-context)
    - [`RequestGlobalsContext`](#requestglobalscontext)
    - [`Helpers`](#helpers)
    - [`WpNonce` is blog-specific](#wpnonce-is-blog-specific)
- [Breaking SRP](#breaking-srp)
- [Installation](#installation)
- [Minimum Requirements](#minimum-requirements)
- [License](#license)
- [Contributing](#contributing)

-------------

# Introduction

WordPress nonces functions does not really work well in an OOP context.

They needs "keys" and "actions" to be passed around, ending up in code that hardcodes those strings
in classes code, or stores them in globally accessible place.

Both solutions are not ideal.

This package aims to provide a way to ease WordPress nonces usage in OOP code.

The specific issues that are addressed are:

- avoid dealing with somehow hardcoded nonce "keys" and "actions"
- have a way to customize nonce TTL on a per nonce basis
- have an approach more suitable for OOP, with enough flexibility to be extended with different
  implementations of nonces
  
# How it works

## Rethinking WordPress workflow

_WordPress_ nonces workflow is:

1. For a "task" a nonce key and an nonce value are put in a request (as URL query variable or via 
   hidden form field). The "key" is just hardcoded, the value is generated with `wp_create_action()` 
   and it is an hash based on an "action" that is specific for the "task";
2. The request handler extracts the nonce value from request data (so it needs  to be aware of the 
   nonce "key") and validates it with `wp_verify_nonce()` that needs to be aware the "action".
   
What we wanted to avoid is to have "keys" and "actions" that needs to be known where the nonce
is _created_ **and** where it is _validated_, causing the issue of tightly coupling between different 
parts of the code as well as the more pragmatic issue of a place to store those values, or to have them
just hardcoded.

_This package_ workflow is:

1. For a "task" a nonce object is created, passing an action string to constructor.
   There's no "key" and the "action" is not needed to be known anywhere else.
2. The request handler, needs to receive (as method argument or as a dependency injected to constructor)
   an instance of the nonce task and use that object ot validate the request.
  
So, using this package, the workflow would be something like (pseudo code):

```php
class TaskForm {

   public function __construct(\Brain\Nonces\NonceInterface $nonce){
     $this->nonce = $nonce;
     $this->url = admin_url('admin-post.php');
   }
   
   public function printFrom() {
      $url = add_query_arg($this->nonce->action(), (string) $this->nonce, $this->url);
      echo "<form action={$url}>";
      // rest of form here...
   }
}

class TaskFormHandler {

   public function __construct(\Brain\Nonces\NonceInterface $nonce){
     $this->nonce = $nonce;
   }
   
   public function saveForm() {
      if (! $this->nonce->validate()) {
        // handle error here...
      }
      
      // continue processing here...
   }
}
```

So the code responsible to build the form and the code responsible to process it, knows nothing
 about "keys" or "actions", nor there's any string hardcoded anywhere.
 
## `NonceInterface` and `WpNonce`

The two classes on the example above receives an instance on `NonceInterface`.

That interface has 3 methods:

- `action()`
- `__toString()`
- `validate()`

The package ships with just one implementation that is called `WpNonce` and wraps WordPress
functions to create and validate the nonce.


## Nonce context

The `validate()` method of `NonceInterface` receives an optional parameter: an instance of 
`NonceContextInterface`.

The reason is that to validate the value it encapsulates, a nonce needs to know what to compare the 
the value to.

This package calls this value to be compared with nonce value "context".

Nonce context is represented by a very simple interface that is no more than an extension of `ArrayAccess`.

The reason is that even if WordPress implementation of nonces requires a string as "context" other 
implementations may require different / more things.

For example, I can imagine a nonce implementation that stores nonce values as user meta, and to verify
that nonce is valid would require not only the value itself, but also an user ID.

Making the context an `ArrayAccess` instance, the package provides as much flexibility as possible
for custom implementations.

## `RequestGlobalsContext`

In the sample pseudo code above, `validate()` is called without passing any context.

The reason is than when not provided (as it is optional) `WpNonce` creates and uses a default 
implementation of `NonceContextInterface` that is `RequestGlobalsContext`.

This implementation uses super globals (`$_GET` and `$_POST`) to "fill" the `ArrayAccess` storage
so that `validate()` will actually uses values from super globals as context when no other context 
is provided.

Being this the most common usage of nonces in WordPress, this simplify operations in large majority 
of cases, still providing flexibility for even very custom implementations.

Just for example, it would be very easy to build a `NonceContextInterface` implementation that takes
its value from HTTP headers (could be useful in REST context), still being able to use the
`WpNonce` class shipped with this package to validate it.


## Helpers

Looking at the sample pseudo code above, when there was the need to "embed" the nonce in the HTML form,
the code uses `add_query_arg()` to add the nonce action and value as URL query variable.

This is something that in core is done with `wp_nonce_url()`, however, that function takes as arguments
"action" and "key" as string and build the nonce value itself. 

Since we want encapsulate the creation of nonce value we can't really use that function.

To provide the same level of "easiness", this package provides a function **`Brain\Nonces\nonceUrl()`**
that receives a nonce instance and an URL string and add the nonce action / value as URL query variable.

The nonce instance is the first function argument and, unlike for WordPress core function, the URL 
string is optional and if not provided defaults to current URL.

However, in case of HTML forms, it is probably better to use a form field instead of a URL query variable.

In WordPress that is done using `wp_nonce_field()`, this package provides **`Brain\Nonces\formField()`** 
that receives a nonce instance and _returns_ the form field HTML markup.

So, the above sample pseudo code could be updated like this:

```php
class TaskForm {

   public function __construct(\Brain\Nonces\NonceInterface $nonce){
     $this->nonce = $nonce;
     $this->url = admin_url('admin-post.php');
   }
   
   public function printFrom() {
      $url = \Brain\Nonces\nonceUrl($this->nonce, $this->url);
      echo "<form action={$url}>";
      // rest of form here...
   }
}
```

or even better like this:

```php
class TaskForm {

   public function __construct(\Brain\Nonces\NonceInterface $nonce){
     $this->nonce = $nonce;
     $this->url = admin_url('admin-post.php');
   }
   
   public function printFrom() {
      echo "<form action={$this->url}>";
      echo \Brain\Nonces\formField($this->nonce);
      // rest of form here...
   }
}
```

Note that these two helpers accept an instance of `NonceInterface` and not of WordPress specific
`WpNonce` class, so they can be used with any custom implementation as well.


## `WpNonce` is blog-specific

It is said above that the `WpNonce` class is a wrapper around WordPress functions.
 
It is true, but besides of using `wp_create_nonce()` / `wp_verify_nonce()`, `WpNonce` automatically 
adds to the action passed to its constructor the current blog id, when calling both those WordPress 
functions.

This ensures that when a nonce was generated in a blog context, will fail validating under another 
blog context.

This is a sanity check that avoid different issues in multisite context with plugins that switch blog 
when, for example, saving post data; preventing to save meta data for posts of a blog into posts
of _another_ blog.


# Breaking SRP

The package provides a couple of interfaces to abstract the nonce workflow, alongside implementations
that just wraps WordPress functions.

The ideal OOP way to make this work, would be having separate interfaces for _nonces_ 
(implemented as value object) and _nonce validators_.

However, following that path every nonce validator would be very specific to a nonce implementation,
because to validate a nonce value a validator needs to be aware of how the nonce was built.

So, probably, the package would need another class, a sort of factory, being able to create 
instances of _nonces_ and _nonces validator_ being compatible each other.

The thing is I thought that this would be really too much, at least in WordPress context.

So I decided to break on purpose the "Single Responsibility Principle" and don't model nonce 
instances as value objects, but as business objects that holds a value _and_ validates against a 
context.

This trade off gave me the chance to only deal with a single object, but still having a decent OOP 
workflow and objects that, even breaking SRP, are no bigger than 50 lines of NCLOC.


# Installation

Via Composer, require `brain\cortex` in version `~1.0.0`.


# Minimum Requirements

- PHP 5.5+
- Composer to install


# License

MIT


# Contributing

See `CONTRIBUTING.md`.

**Don't** use issue tracker (nor send any pull request) if you find a **security** issue.
They are public, so please send an email to the address on my [Github profile](https://github.com/Giuseppe-Mazzapica). Thanks.
