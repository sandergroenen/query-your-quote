# Query Your Quote

A modern web application that generates random quotes and displays performance metrics in real-time. Built with Laravel 12, Inertia.js, and React.

![Query Your Quote](https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg)

## About Query Your Quote

Query Your Quote is a web application that demonstrates modern web development practices using Laravel and React. The application:

- Fetches random quotes from the DummyJSON API
- Displays quotes with author attribution
- Shows real-time request performance metrics with a dynamic speedometer
- Provides user authentication and profile management
- Demonstrates proper API integration and error handling

## Tech Stack

- **Backend**: Laravel 12 with PHP 8.4
- **Frontend**: React 18 with Inertia.js
- **Styling**: Tailwind CSS
- **Development**: Docker, Vite
- **API Integration**: DummyJSON API
- **Authentication**: Laravel Breeze
- **Deployment**: AWS ECS, ECR, CloudFormation

## Getting Started

### Prerequisites

- Docker Engine installed and running
- Git

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/sandergroenen/query-your-quote.git
   cd query-your-quote
   ```

2. Linux/MacOS Run the start script:
   ```bash
   chmod +x scripts/start-local.sh
   ./scripts/start-local.sh
   ```
   Windows:
   ```powershell
   # Run in PowerShell
   .\scripts\start-local.ps1
   ```

That's it! The script will:
- Build and start Docker containers
- Install all dependencies
- Set up the environment
- Run database migrations and seeders
- Start the development server

### Accessing the Application

Once the setup is complete, you can access the frontend application at:
- **URL**: http://localhost
- **Default credentials**:
  - Email: user@example.com
  - Password: password
- the application features an api endpoint that can be called to get a random quote but provides theb above mentioned front-end for easy demonstration. If you want to access the api endpoint using curl or another tool directly please make a POST call to http://localhost/quotes/random which will return the quotes from the api directly

## Features

- **Quote Generation**: Get random quotes with a single click
- **Rate limited**: The api is rate limited by default to 1 per 10 seconds, the frontend has a visual representation of the rate limiting
- **Performance Metrics**: View request time metrics with a dynamic speedometer
- **User Authentication**: Register, login, and manage your profile (on frontend)
- **Responsive Design**: Works on desktop and mobile devices
- **Docker**: the application and all needed tools are run inside docker containers
- **Github actions testrun & deployment on AMAZON**: aside from being able to run the application locally the applications will run tests automatically on github actions upon pushing code and also deploy to amazon aws ecs


## Development

### Project Structure

- `app/` - Laravel application code
- `resources/js/` - React components and pages
- `resources/js/Components/Quote/` - Quote-related components
- `routes/` - API and web routes
- `scripts/` - Utility scripts for development

### Key Components

#### application logic
- `RandomQuote.jsx` - Main component for displaying quotes
- `DummyJsonService.php` - Service for interacting with the DummyJSON API
- `QuoteController.php` - API controller for quote-related endpoints

#### infrastructure
- `docker-compose.yml` - Docker compose file for setting up development environment
- Dockerfile` - Dockerfile for building the application container
- `cloudformation.yml` - AWS CloudFormation template for setting up production environment in AWS ECS
- `.github/deployment.yml` - Github actions deployment file for automatic build pipeline to deploy cloudformation stack on AWS
- `.github/test.yml` - Github actions deployment file for automatic build pipeline to deploy cloudformation stack on AWS



## AWS Deployment

This project includes a complete CI/CD pipeline for deploying to AWS ECS (Elastic Container Service) using GitHub Actions and CloudFormation.

### Prerequisites for AWS Deployment

1. An AWS account with appropriate permissions
2. The following AWS services will be used:
   - ECR (Elastic Container Registry)
   - ECS (Elastic Container Service)
   - EC2 (for ECS host instances)
   - CloudFormation
   - IAM (for service roles)
   - SSM Parameter Store (for secrets)
   - CloudWatch (for logs)

### Deployment Setup

1. **Fork the Repository**:
   Fork this repository to your own GitHub account or create a new repository and push the code there.

2. **Configure GitHub Secrets**:
   In your GitHub repository, go to Settings > Secrets and add the following secrets:
   - `AWS_ACCESS_KEY_ID`: Your AWS access key
   - `AWS_SECRET_ACCESS_KEY`: Your AWS secret key
   - `AWS_REGION`: Your preferred AWS region (e.g., `us-east-1`)
   - `DB_APP_ROOT_PASSWORD`: A secure password for the database

3. **Initial Deployment**:
   - Push to the `main` branch or manually trigger the workflow from the Actions tab
   - The GitHub Actions workflow will:
     - Set up AWS credentials
     - Create necessary SSM parameters for secrets
     - Create an ECR repository if it doesn't exist
     - Build and push the Docker image to ECR
     - Deploy the CloudFormation stack
     - Run database migrations

4. **Deployment Process**:
   - The workflow automatically deploys when you push to the `main` branch
   - The CloudFormation template creates all necessary AWS resources:
     - VPC with public subnets
     - Security groups
     - IAM roles
     - ECS cluster and service
     - Load balancer
     - Auto-scaling group
   - The application is deployed as a containerized service on ECS
   - Database migrations are run automatically

5. **Accessing Your Deployed Application**:
   - After deployment completes, find the load balancer URL in the AWS Console
   - The URL will be available in the CloudFormation stack outputs

### Monitoring and Troubleshooting

- **Logs**: Application logs are sent to CloudWatch
- **Metrics**: ECS provides metrics for container performance
- **Debugging**: You can connect to the ECS instances for debugging if needed

### Common Deployment Issues

1. **Database Migration Failures**:
   - The GitHub Actions workflow includes a robust migration mechanism that tries multiple approaches
   - If automatic migrations fail, you can manually run migrations by connecting to the EC2 instance via SSM:
     ```bash
     # Find the instance ID
     aws ec2 describe-instances --filters "Name=tag:aws:autoscaling:groupName,Values=<your-auto-scaling-group>" --query "Reservations[0].Instances[0].InstanceId" --output text
     
     # Connect via SSM
     aws ssm start-session --target <instance-id>
     
     # Find and run migrations in the container
     docker ps
     docker exec <container-id> php artisan migrate --force
     ```

2. **URL Masking in GitHub Actions**:
   - GitHub Actions might mask parts of URLs in the output if they contain patterns that look like secrets
   - The deployment workflow now splits the URL output to prevent masking
   - If you still see masked output (like `%2A%2A%2A`), you can always find the Load Balancer URL in the AWS Console under EC2 > Load Balancers

3. **Application Not Accessible**:
   - Check the security groups to ensure they allow traffic on port 80
   - Verify the health checks are passing in the load balancer configuration
   - Check the ECS service events for any deployment issues

## License

The Query Your Quote application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
