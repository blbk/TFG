<# ============================================================================
   Proyecto      : Sistema de Gestión CMDB para TFG
   Archivo       : script_CI.ps1
   Autor         : Javier Moyano Vizcaíno
   Curso         : 2025/2026
  
   Descripción	 : Script para identificar los datos del sistema en Windows 10/11
				   Usa:
						Variables de entorno
						la infraestructura WMI/CIM 
						API de red de Windows
						Resolución DNS local
						ee los datos UEFI EDID del monitor
   Observaciones : Si es un portátil se obvia el monitor principal (integrado)
   ============================================================================
#>
Set-StrictMode -Version Latest

function ObtenerValor {
    param(
        [scriptblock]$Script,
        [string]$PorDefecto = "No disponible"
    )
    try {
        $valor = & $Script
        if ([string]::IsNullOrWhiteSpace([string]$valor)) { 
            return $PorDefecto 
        }
        $valor
    }
    catch {
        $PorDefecto
    }
}

function ObtenerVersionNavegador {
    param([string[]]$Rutas)

    foreach ($ruta in $Rutas) {
        if (Test-Path $ruta) {
            try {
                return (Get-Item $ruta).VersionInfo.ProductVersion
            } catch {
                return "No disponible"
            }
        }
    }
    "No instalado / no encontrado"
}

function ObtenerUsuarioActual {
    try {
        $nombre = (Get-CimInstance Win32_ComputerSystem -Property UserName -ErrorAction Stop).UserName
        if ([string]::IsNullOrWhiteSpace($nombre)) { return "No disponible" }
        $nombre
    } catch {
        "No disponible"
    }
}

function ObtenerUltimoInicioSesion {
    try {
        $eventos = Get-WinEvent -FilterHashtable @{
            LogName = 'Security'
            Id      = 4624
        } -MaxEvents 200 -ErrorAction Stop

        foreach ($evt in $eventos) {
            [xml]$xml = $evt.ToXml()
            $datos = @{}
            foreach ($item in $xml.Event.EventData.Data) {
                if ($item.Name) { $datos[$item.Name] = $item.'#text' }
            }

            $tipoInicio = $datos['LogonType']
            $usuario    = $datos['TargetUserName']
            $dominio    = $datos['TargetDomainName']

            if ($tipoInicio -in '2','7','10','11' -and
                $usuario -and
                $usuario -notin 'SYSTEM','LOCAL SERVICE','NETWORK SERVICE' -and
                $dominio -notin 'NT AUTHORITY','Window Manager','Font Driver Host') {

                return [pscustomobject]@{
                    FechaUltimoLogin   = $evt.TimeCreated
                    UsuarioUltimoLogin = if ($dominio) { "$dominio\$usuario" } else { $usuario }
                }
            }
        }

        [pscustomobject]@{
            FechaUltimoLogin   = "No disponible"
            UsuarioUltimoLogin = "No disponible"
        }
    }
    catch {
        [pscustomobject]@{
            FechaUltimoLogin   = "No disponible"
            UsuarioUltimoLogin = "No disponible"
        }
    }
}

function ObtenerInfoAntivirus {
    $resultado = [ordered]@{
        Antivirus                  = "No disponible"
        EstadoAntivirus            = "No disponible"
        FechaActualizacionAntivirus = "No disponible"
    }

    try {
        $av = Get-CimInstance -Namespace root/SecurityCenter2 -ClassName AntivirusProduct -ErrorAction Stop |
              Select-Object -First 1

        if ($av) {
            $resultado.Antivirus = $av.displayName
            $resultado.EstadoAntivirus = $av.productState

            try {
                $def = Get-MpComputerStatus -ErrorAction Stop
                $resultado.FechaActualizacionAntivirus = $def.AntivirusSignatureLastUpdated
            } catch {
                $resultado.FechaActualizacionAntivirus = "No disponible"
            }
        }
    }
    catch {
        try {
            $def = Get-MpComputerStatus -ErrorAction Stop
            $resultado.Antivirus = "Microsoft Defender"
            $resultado.EstadoAntivirus = if ($def.AntivirusEnabled) { "Activo" } else { "Inactivo" }
            $resultado.FechaActualizacionAntivirus = $def.AntivirusSignatureLastUpdated
        } catch {
        }
    }

    [pscustomobject]$resultado
}

$equipo = Get-CimInstance Win32_ComputerSystem
$sistema = Get-CimInstance Win32_OperatingSystem
$bios = Get-CimInstance Win32_BIOS
$discos = Get-CimInstance Win32_LogicalDisk -Filter "DriveType=3"
$infoAntivirus = ObtenerInfoAntivirus
$infoLogin = ObtenerUltimoInicioSesion

$discoTotal = ($discos | Measure-Object Size -Sum).Sum
$discoLibre = ($discos | Measure-Object FreeSpace -Sum).Sum

$rutasChrome = @(
    "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe"
)

$rutasEdge = @(
    "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe",
    "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
)

$resultado = [pscustomobject]@{
    nombreLocal                 = $env:COMPUTERNAME
    HostnameRed                 = ObtenerValor { [System.Net.Dns]::GetHostByName($env:COMPUTERNAME).HostName }
    DireccionIP                 = ObtenerValor { (Get-NetIPAddress -AddressFamily IPv4 |
                                       Where-Object { $_.IPAddress -notlike '169.254*' -and $_.IPAddress -ne '127.0.0.1' } |
                                       Select-Object -First 1 -ExpandProperty IPAddress) }
	Gateway                    = ObtenerValor {
                                    (Get-NetRoute -DestinationPrefix '0.0.0.0/0' |
                                     Sort-Object RouteMetric |
                                     Select-Object -First 1 -ExpandProperty NextHop) }
    DireccionMAC                = ObtenerValor { (Get-NetAdapter -Physical |
                                       Where-Object Status -eq 'Up' |
                                       Select-Object -First 1 -ExpandProperty MacAddress) }
	Dominio 					= ObtenerValor { (Get-CimInstance Win32_ComputerSystem).Domain }
    Marca                       = $equipo.Manufacturer
    Modelo                      = $equipo.Model
	NumeroSerie                 = $bios.SerialNumber
    Arquitectura                = if ([Environment]::Is64BitOperatingSystem) { '64 bits' } else { '32 bits' }
    DiscoTotalGB                = [math]::Round($discoTotal / 1GB, 1)
    DiscoLibreGB                = [math]::Round($discoLibre / 1GB, 1)
    MemoriaGB                   = [math]::Round($equipo.TotalPhysicalMemory / 1GB, 0)
    SistemaOperativo            = $sistema.Caption
    VersionSO                   = $sistema.Version
    UsuarioActual               = ObtenerUsuarioActual
    UsuarioUltimoLogin          = $infoLogin.UsuarioUltimoLogin
	FechaUltimoLogin            = $infoLogin.FechaUltimoLogin
    FechaActualizacionAntivirus = $infoAntivirus.FechaActualizacionAntivirus	
    Antivirus                   = $infoAntivirus.Antivirus
    EstadoAntivirus             = $infoAntivirus.EstadoAntivirus
	VersionChrome               = ObtenerVersionNavegador -Rutas $rutasChrome
    VersionEdge                 = ObtenerVersionNavegador -Rutas $rutasEdge
}

$resultado | Format-List

$rutaCSV = Join-Path $PSScriptRoot "$($env:COMPUTERNAME).csv"
$resultado | Export-Csv -Path $rutaCSV -NoTypeInformation -Encoding UTF8
Write-Host "Datos exportados a: $rutaCSV"
