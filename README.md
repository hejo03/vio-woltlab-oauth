# vio-woltlab-oauth

## Installation

1. Plugin herunterladen: Lade com.hejo03.viooauth.tar.gz  herunter.
2. **Installation durchführen:**
   Gehe ins ACP > Konfiguration > Pakete verwalten > Paket installieren & installiere das Packet.
3. **Konfiguration:**
   Gehe im ACP auf Vio-V OAuth und gebe Client-ID & Client-Secret an.
4. **Optional:** E-Mail Login entfernen. (nicht sicher)
```scss
#loginForm {
  > dl,
  > .formSubmit,
  .authOtherOptionButtons__separator {
    display: none;
  }
  .authOtherOptionButtons {
    margin: 0;
  }
}
```