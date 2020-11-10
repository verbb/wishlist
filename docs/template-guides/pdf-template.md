# PDF Template
You can also generate a PDF version of your lists, for your users to download and save, or share with other users.

To get started, ensure you've setup the **PDF Template** under the [Configuration](docs:get-started/configuration) for Wishlist. Feel free to adjust any additional PDF-related options as required.

Then, you'll want to fetch the list you want to generate the PDF for.

```twig
{% set list = craft.wishlist.lists().default(true).one() %}

{% if list %}
    <a href="{{ list.getPdfUrl() }}">Download your wishlist</a>
{% endif %}
```

Here, we're generating a link for the user to click on to download their PDF. When clicked, their PDF will be downloaded. The URL will look look something similar to:

`https://craft.test/actions/wishlist/pdf?listId=123`

### Additional parameters
You may find the additional parameters useful, especially during testing and development of these templates. Simply use one of the following values to append to the URL produced above.

- `&attach=false` - Add this to not force the PDF to download. Instead, it'll be rendered inside the browser window. This will still render as a PDF and is useful for debugging layout issues.
- `&format=plain` - Produces the same template as HTML, as opposed to PDF. Again, useful for debugging layout issues, or quickly prototyping layouts.

## Template
In order for the user do have something to download, you'll want to generate the actual template used by the PDF. Create a template for the path you've set in your **PDF Path** under the [Configuration](docs:get-started/configuration). By default, the path to the PDF template is `_pdf/wishlist`. 

### Template variables
In the template itself, you'll have access to the following Twig variables:

#### list
The [List](docs:developers/list) object

### Example template

Below is an extremely basic example, we encourage you to get creative to make great-looking templates!

```twig
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<style>
    html {
        margin-top:0.2in !important;
        margin-left:0.2in !important;
    }

    body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 13px;
        line-height:1.4em;
        font-weight:bold;
    }
</style>
</head>

<body>
{% for item in list.items.all() %}
    <div>
        <p>
            <strong>Name:</strong> {{ item.title }}<br>
            <strong>URL:</strong> {{ item.element.link }}<br>
            <strong>Created:</strong> {{ item.dateCreated | date('short') }} {{ item.dateCreated | time('short') }}
        </p>
    </div>
{% endfor %}
</body>
</html>
```
