output "webserver_public_ips" {
  value = join(",", [for i in module.myapp-webserver : i.aws_instance.public_ip])
}
