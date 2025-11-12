#include <stdio.h>
#include <stdlib.h>
#include <errno.h>
#include <time.h>
#include <ctype.h>
#include <string.h>

void urldecode2(char *dst, char *src)
{
        char a, b;
        while (*src) {
                if ((*src == '%') &&
                    ((a = src[1]) && (b = src[2])) &&
                    (isxdigit(a) && isxdigit(b))) {
                        if (a >= 'a')
                                a -= 'a'-'A';
                        if (a >= 'A')
                                a -= ('A' - 10);
                        else
                                a -= '0';
                        if (b >= 'a')
                                b -= 'a'-'A';
                        if (b >= 'A')
                                b -= ('A' - 10);
                        else
                                b -= '0';
                        *dst++ = 16*a+b;
                        src+=3;
                } else if (*src == '+') {
                        *dst++ = ' ';
                        src++;
                } else {
                        *dst++ = *src++;
                }
        }
        *dst++ = '\0';
}
int main ()
{
   char *env_value;
   char *save_env;

   printf("Content-type: text/html\n\n");
   save_env = getenv("QUERY_STRING");
   if (strncmp(save_env, "q=", 2) == 0) {
        memmove(save_env, save_env + 2, strlen(save_env + 2) + 1);
    }

   char *decoded = (char *)malloc(strlen(save_env) + 1);

   urldecode2(decoded, save_env);


   char first_part[] = "grep -R -i ";
   char last_part[] = " /var/www/html/data/printer_drivers.txt" ;
   size_t totalLength = strlen(first_part) + strlen(last_part) + strlen(decoded) + 1;
   char *combinedString = (char *)malloc(totalLength);
   if (combinedString == NULL) {
        printf("Failed to allocate memory");
        return 1;
   }
   strcpy(combinedString, first_part);
   strcat(combinedString, decoded);
   strcat(combinedString, last_part);
   FILE *fp;
   char buffer[1024];

   fp = popen(combinedString, "r");
   if (fp == NULL) {
      printf("Error running command\n");
      return 1;
   }
   while (fgets(buffer, sizeof(buffer), fp) != NULL) {
      printf("%s<br>", buffer);
   }

   pclose(fp);

   fflush(stdout);
   free(combinedString);
   free(decoded);
   exit (0);
}
