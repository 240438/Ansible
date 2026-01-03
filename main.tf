provider "aws" {
  shared_config_files      = ["~/.aws/config"]
  shared_credentials_files = ["~/.aws/credentials"]
}

resource "aws_vpc" "myapp_vpc" {
  cidr_block = var.vpc_cidr_block
  tags = {
     Name = "${var.env_prefix}-vpc"
  }
}

module "myapp-subnet" {
  source = "./modules/subnet"
  vpc_id = aws_vpc.myapp_vpc.id
  subnet_cidr_block = var.subnet_cidr_block
  availability_zone = var.availability_zone
  env_prefix = var.env_prefix
  default_route_table_id = aws_vpc.myapp_vpc.default_route_table_id
}

module "myapp-webserver" {
  source = "./modules/webserver"
  env_prefix = var.env_prefix
  instance_type = var. instance_type
  availability_zone = var.availability_zone
  public_key = var.public_key
  my_ip = local.my_ip
  vpc_id = aws_vpc.myapp_vpc.id
  subnet_id = module.myapp-subnet.subnet.id
  
  # Loop count
  count             = 1
  # Use count.index to differentiate instances
  instance_suffix   = count.index
}

resource "null_resource" "configure_server" {
  triggers = {
    webserver_public_ips = join(",", [for i in module.myapp-webserver : i.aws_instance.public_ip])
  }

  depends_on = [module.myapp-webserver]

  provisioner "local-exec" {
    #command = "echo Webserver IPs for Ansible: ${self.triggers.webserver_public_ips_for_ansible}"
    # command = <<-EOT
    #             ansible-playbook -i "$(terraform output -raw webserver_public_ips_for_ansible)," \
    #             -e "normal_user=ec2-user docker_compose_file_location=/workspace/Ansible" \
    #             my-playbook.yaml
    #             EOT
    command = <<-EOT
                echo Webserver IPs for Ansible: ${self.triggers.webserver_public_ips}
                
                ansible-playbook -i ${self.triggers.webserver_public_ips}, \
                --private-key "${var.private_key}" --user ec2-user \
                my-playbook.yaml
                EOT
  }
}