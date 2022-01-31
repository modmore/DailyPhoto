# Daily Photo

Adds a random photo from [Unsplash](https://unsplash.com/) to the MODX3 login screen every day.

After installation:

1. [Create an application with Unsplash](https://unsplash.com/oauth/applications) 
2. Copy/paste the **Access Key** to the `daily_photo.access_key` system setting.
3. Optionally, configure a custom search query with the `daily_photo.query` system setting that fits with your brand.

The image is cached based on the date, so you'll start seeing a new one after midnight server time.
