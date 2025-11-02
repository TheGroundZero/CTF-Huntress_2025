# Tabby's Date

| Category     | Author       |
| ------------ | ------------ |
| 🔍 Forensics | John Hammond |

> Ohhhh, Tab, Tab, Tab.... what has she done.
> 
> My friend Tabby just got a new laptop and she's been using it to take notes. She says she puts her whole life on there!
> 
> She was so excited to finally have a date with a boy she liked, but she completely forgot the details of where and when. She told me she remembers writing it in a note... but she doesn't think she saved it!!
> 
> She shared with us an export of her laptop files.

> **NOTE**
> 
> The password to the ZIP archive is `tabbys_date`.

> Can you help us find the details of Tab's date?

Export looks like a Windows back-up

Notepad tabs back-up?

```
./AppData/Local/Packages/Microsoft.WindowsNotepad_8wekyb3d8bbwe/LocalState/TabState:
002d2531-9aff-42b1-b54d-b178c88063b4.bin  2e0dd6b6-ba93-4efc-9fd4-985dad74869a.bin  68fefe2f-a7a6-4afa-b383-7fdc142aadde.bin  a16d5079-b2f7-4a54-b3d5-b32256c4f238.bin  c3cbe154-ef26-4e93-9183-c7fd323fe8c0.bin  e6a849ab-6f02-452c-98e7-cdb03c577818.bin
04165ca3-c82b-42ca-ab07-0c774ae66efd.bin  414e4071-60e6-4bb6-9a5a-f1e5bf6fe79c.bin  711f26f1-0eff-4a34-a78c-03562e44a36b.bin  a2048a5f-5cb5-460d-8ce6-70899de24d9c.bin  c4b77218-ef21-4a7f-9814-e4444f82475a.bin  e86c9910-afca-4e83-87f6-600ed08a0570.bin
056941ef-d51d-4e57-9a55-b59d58bf3fcb.bin  45dcdbe4-26b5-4e0b-ba2d-29e9e9c1e11b.bin  7458196e-e979-4d94-982a-246fca3db028.bin  a9da0602-fcd2-4793-9bab-70276e881006.bin  cb2f0c84-6293-4e63-8575-78dc879945e0.bin  ed9b5775-f35a-4770-a35e-e3c24b8bed47.bin
14623d59-ad8c-43a8-b669-587f049a1516.bin  4f1c96a1-960c-4cee-9751-fe4b4f59fdd0.bin  7ba066a2-e0cb-4c06-9339-316411a3da27.bin  af1fbc46-41cb-4d4b-9c34-02b874bfe9c6.bin  cd01dd8e-32f6-4f88-b9bb-4009afca3fea.bin  f1473e57-7637-4bd0-8158-53715ea20630.bin
17de440f-3f69-4d8a-94fe-f3d4b9cf0c3f.bin  5a57ac85-7e99-4bfc-9e13-f0d28a2bcc20.bin  9925cc8a-6440-4128-acae-f31541130a5e.bin  b5074fe7-4f54-4728-afe9-1c063d211a82.bin  dcfa4d00-41c8-439a-b1bd-2706dd8dbe0d.bin
1aebb59c-5d51-41f1-918e-dec9e1a28ce1.bin  66f955a8-6994-47c6-8326-0f128dafd0b9.bin  9bf7ca49-e491-4691-a21a-f3263bb695a2.bin  b5154796-9d23-43ce-8a6c-c373e63f22c0.bin  dea21c9d-4534-4d38-a60b-0a5c1b9b5928.bin
2d755c27-5840-47ad-a4ca-ed8041dd3047.bin  68d7e607-77c4-4d35-8ef2-0170a84efe5f.bin  9e96bd4b-4155-4558-b97a-edcdf01d4584.bin  bcd5d203-1523-4b86-a572-c1c3afded478.bin  e21dc9ae-2a03-42bf-8972-35ce8d524695.bin
```

Those bin files contain UTF-16LE content.

```sh
$hexdump -C 002d2531-9aff-42b1-b54d-b178c88063b4.bin | head -10
00000000  4e 50 00 00 01 00 00 01  00 00 03 01 01 01 00 00  |NP..............|
00000010  81 20 48 e2 00 00 b0 01  72 00 61 00 6e 00 64 00  |. H.....r.a.n.d.|
00000020  6f 00 6d 00 20 00 74 00  68 00 6f 00 75 00 67 00  |o.m. .t.h.o.u.g.|
00000030  68 00 74 00 73 00 20 00  74 00 6f 00 64 00 61 00  |h.t.s. .t.o.d.a.|
00000040  79 00 20 00 74 00 68 00  69 00 73 00 20 00 77 00  |y. .t.h.i.s. .w.|
00000050  65 00 65 00 6b 00 20 00  3c d8 55 df 0d 00 0d 00  |e.e.k. .<.U.....|
00000060  70 00 73 00 61 00 20 00  74 00 6f 00 20 00 6d 00  |p.s.a. .t.o. .m.|
00000070  65 00 3a 00 0d 00 2a 00  20 00 66 00 72 00 69 00  |e.:...*. .f.r.i.|
00000080  64 00 61 00 79 00 20 00  34 00 70 00 6d 00 20 00  |d.a.y. .4.p.m. .|
00000090  67 00 79 00 6d 00 2c 00  20 00 62 00 72 00 69 00  |g.y.m.,. .b.r.i.|
```

Grab text with `strings`

`-el` is needed because of the little-endian 16-bit encoding

```sh
$ strings -el 002d2531-9aff-42b1-b54d-b178c88063b4.bin | head -10
random thoughts today this week 
psa to me:
* friday 4pm gym, bring sneakers + water lol
* download practice test lol
* print english essay omg
* ask ms. clark about #7 omg
```

Grab flag

```sh
$ strings -el *.bin | grep flag
they told me the password is: flag{165d19b610c02b283fc1a6b4a54c4a58}
```

`flag{165d19b610c02b283fc1a6b4a54c4a58}`