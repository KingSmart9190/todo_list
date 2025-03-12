# Use an official PHP image from Docker Hub
FROM php:8.1-cli

# Set the working directory inside the container
WORKDIR /app

# Copy your application files into the container
COPY . .

# Expose the port that Render uses
ENV PORT=8000

# Start the PHP built-in server on the specified port
CMD ["php", "-S", "0.0.0.0:$PORT"]
